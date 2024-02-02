<?php

namespace Mg\Negocio;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Mg\Titulo\Titulo;
use Mg\Portador\Portador;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pdv\PdvNegocioService;

class NegocioService
{
    const STATUS_ABERTO = 1;
    const STATUS_FECHADO = 2;
    const STATUS_CANCELADO = 3;

    public static function fecharSePago (Negocio $negocio)
    {
        static::recalcularTotal($negocio);
        if ($negocio->codnegociostatus != NegocioStatus::ABERTO) {
            return false;
        }
        $valorpagamento = floatval($negocio->NegocioFormaPagamentoS()->sum('valorpagamento'));
        if (($negocio->valortotal - $valorpagamento) > 0.01) {
            return false;
        }
        return static::fechar($negocio);
    }

    public static function recalcularTotal(Negocio $negocio)
    {
        $negocio->valorjuros = $negocio->NegocioFormaPagamentoS()->sum('valorjuros');
        $negocio->valortotal = 
            $negocio->valorprodutos 
            - $negocio->valordesconto
            + $negocio->valorfrete
            + $negocio->valorseguro
            + $negocio->valoroutras
            + $negocio->valorjuros;
        $negocio->save();
    }

    public static function fechar (Negocio $negocio)
    {
        // se for PDV, utiliza rotina de fechamento daquela classe
        if (!empty($negocio->codpdv)) {
            return PdvNegocioService::fechar($negocio, $negocio->Pdv);
        }

        if ($negocio->codnegociostatus != NegocioStatus::ABERTO) {
            throw new \Exception("O Status do Negócio não permite Fechamento!", 1);
        }

        if ($negocio->NegocioProdutoBarras()->count() == 0) {
            throw new \Exception("Não foi informado nenhum produto neste negócio!", 1);
        }

        if ($negocio->NaturezaOperacao->venda) {
            if (!\Mg\Pessoa\PessoaService::podeVenderAPrazo($negocio->Pessoa, $negocio->valoraprazo)) {
                throw new \Exception("Solicite Liberação de Crédito ao Departamento Financeiro!", 1);
            }
        }

        //Calcula total pagamentos à vista e à prazo
        $valorPagamentos = 0;
        $valorPagamentosPrazo = 0;
        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            $valorPagamentos += ($nfp->valortotal)?$nfp->valortotal:$nfp->valorpagamento;
            if (!$nfp->FormaPagamento->avista) {
                $valorPagamentosPrazo += $nfp->valortotal;
            }
        }

        //valida total pagamentos
        if (($negocio->valortotal - $valorPagamentos) >= 0.01) {
            throw new \Exception("O valor dos Pagamentos ({$valorPagamentos}) é inferior ao Total ({$negocio->valortotal})!", 1);
        }

        //valida total à prazo
        if ($valorPagamentosPrazo > $negocio->valortotal) {
            throw new \Exception("O valor a prazo ({$valorPagamentosPrazo}) é superior ao Total ({$negocio->valortotal})!", 1);
        }

        //gera títulos
        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            if (!static::gerarTitulos($nfp)) {
                throw new \Exception("Falha ao gerar os Títulos!", 1);
                return false;
            }
        }

        //atualiza status
        $negocio->update([
            'codnegociostatus' => NegocioStatus::FECHADO,
            'codusuario' => Auth::user()->codusuario??$negocio->codusuario,
            'lancamento' => Carbon::now()
        ]);

        return static::movimentaEstoque($negocio);

    }

    public static function movimentaEstoque(Negocio $negocio)
    {
        // Chama MGLara para fazer movimentacao do estoque com delay de 10 segundos
        $url = env('MGLARA_URL') . "estoque/gera-movimento-negocio/{$negocio->codnegocio}?delay=10";
        $ret = json_decode(file_get_contents($url));
        if (@$ret->response !== 'Agendado') {
            dd($ret);
            return false;
        }
        return true;
    }

    public static function gerarTitulos (NegocioFormaPagamento $nfp)
    {
        //se for avista ignora
        if ($nfp->FormaPagamento->avista) {
            return true;
        }

        //se ja tem titulos gerados gera erro
        if (count($nfp->Titulos) != 0) {
            $nfp->addError("codformapagamento", "Já existem Títulos gerados para a forma de pagamento, impossível gerar novos!");
            return false;
        }

        $total = 0;
        $parcelas = $nfp->FormaPagamento->parcelas;

        // faz um looping para gerar duplicatas
        for ($i = 1; $i <= $parcelas; $i++) {

            //Joga diferença no último titulo gerado
            if ($i == $parcelas) {
                $valor = $nfp->valorpagamento - $total;
            } else {
                $valor = floor($nfp->valorpagamento / $parcelas);
            }
            $total += $valor;

            $titulo = new Titulo();
            $titulo->codnegocioformapagamento = $nfp->codnegocioformapagamento;
            $titulo->codfilial = $nfp->Negocio->codfilial;
            $titulo->codtipotitulo = $nfp->Negocio->NaturezaOperacao->codtipotitulo;
            $titulo->codcontacontabil = $nfp->Negocio->NaturezaOperacao->codcontacontabil;
            if ($nfp->Negocio->NaturezaOperacao->codoperacao == Operacao::SAIDA) {
                $titulo->debito = $valor;
            } else {
                $titulo->credito = $valor;
            }
            $titulo->boleto = $nfp->FormaPagamento->boleto;
            $titulo->codpessoa = $nfp->Negocio->codpessoa;
            $titulo->numero = "N" . str_pad($nfp->codnegocio, 8, "0", STR_PAD_LEFT) . "-$i/{$parcelas}";
            $titulo->emissao = Carbon::now();
            $titulo->transacao = $titulo->emissao;
            $titulo->sistema = $titulo->emissao;
            $titulo->vencimento = $titulo->emissao->addDays($i * $nfp->FormaPagamento->diasentreparcelas);
            $titulo->vencimentooriginal = $titulo->vencimento;
            $titulo->gerencial = true;

            //se for boleto pega o primeiro portador bancario da filial
            if ($titulo->boleto) {
                $portador = Portador::where('codfilial', $titulo->codfilial)->where('emiteboleto', true)->first();
                if ($portador) {
                    $titulo->codportador = $portador->codportador;
                }
            }

            //se deu erro ao salvar titulo aborta
            if (!$titulo->save()) {
                return false;
            }
        }
        return true;
    }

}
