<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use DB;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioStatus;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\Titulo\BoletoBb\BoletoBbService;
use Mg\Titulo\TituloService;
use Illuminate\Support\Facades\Log;

class PdvNegocioService
{

    public static function negocio($data, Pdv $pdv)
    {
        // Procura se já existe um negocio com o uuid na base
        $negocio = Negocio::firstOrNew(['uuid' => $data['uuid']]);

        // Se Fechado/Cancelado
        if (in_array($negocio->codnegociostatus, [2, 3])) {
            return static::negocioFechado($negocio, $data, $pdv);
        }

        // Se Aberto
        return static::negocioAberto($negocio, $data, $pdv);
    }


    public static function negocioAberto(Negocio $negocio, $data, Pdv $pdv)
    {

        // importa os dados do negocio
        $negocio->fill($data);
        $negocio->codpdv = $pdv->codpdv;
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->save();

        // importa os itens
        foreach ($data['itens'] as $item) {
            $npb = NegocioProdutoBarra::firstOrNew(['uuid' => $item['uuid']]);
            if (!empty($npb->codnegocio) && $npb->codnegocio != $negocio->codnegocio) {
                throw new Exception("Tentando atualizar um item de outro negocio {$npb->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $npb->fill($item);
            $npb->codnegocio = $negocio->codnegocio;
            $npb->save();
        }

        // importa os pagamentos
        foreach ($data['pagamentos'] as $pagto) {
            // ignora pagamentos criados por integracao de algum sistema
            if ($pagto['integracao']) {
                continue;
            }
            // procura se pagamento já existe
            $nfp = NegocioFormaPagamento::firstOrNew(['uuid' => $pagto['uuid']]);
            if (!empty($nfp->codnegocio) && $nfp->codnegocio != $negocio->codnegocio) {
                throw new Exception("Tentando atualizar um pagamento de outro negocio {$nfp->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            // vincula pagamento
            $nfp->fill($pagto);
            $nfp->codnegocio = $negocio->codnegocio;
            $nfp->save();
        }

        // exclui pagamentos que nao vieram no post
        $uuids = array_column($data['pagamentos'], 'uuid');
        NegocioFormaPagamento::where('codnegocio', $negocio->codnegocio)->where('integracao', false)->whereNotIn('uuid', $uuids)->delete();

        return $negocio;
    }

    public static function negocioFechado(Negocio $negocio, $data, Pdv $pdv)
    {
        if ($negocio->valortotal != $data['valortotal']) {
            throw new Exception("Não é permitido alterar os valores de um negocio Fechado ou Cancelado {$negocio->codnegocio} {$negocio->valortotal} != {$data['valortotal']}!", 1);
        }
        if ($negocio->NaturezaOperacao->financeiro != $data['financeiro']) {
            throw new Exception("Não é permitido alterar de uma Natureza que não gera financeiro para outra que gera, ou vice-versa {$negocio->codnegocio}!", 1);
        }

        $natNova = NaturezaOperacao::findOrFail($data['codnaturezaoperacao']);
        if ($negocio->NaturezaOperacao->codoperacao !== $natNova->codoperacao) {
            throw new Exception("Não é permitido alterar de uma Natureza de Saída para outra de Entrada ou vice-versa {$negocio->codnegocio}!", 1);
        }

        // Não alterar usuario, pdv nem data de lançamento
        unset($data['codusuario']);
        unset($data['lancamento']);
        unset($data['codpdv']);
        $negocio->fill($data);
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->save();

        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            foreach ($nfp->TituloS as $titulo) {
                $titulo->codpessoa = $negocio->codpessoa;
                $titulo->codtipotitulo = $natNova->codtipotitulo;
                $titulo->codcontacontabil = $natNova->codcontacontabil;
                $titulo->save();
            }
        }

        // agenda movimentacao de estoque
        static::movimentarEstoque($negocio);

        return $negocio;
    }


    public static function movimentarEstoque(Negocio $negocio)
    {
        // Chama MGLara para fazer movimentacao do estoque com delay de 10 segundos
        $url = env('MGLARA_URL') . "estoque/gera-movimento-negocio/{$negocio->codnegocio}?delay=10";
        try {
            $ret = json_decode(file_get_contents($url, false, stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ])));
            if (@$ret->response !== 'Agendado') {
                Log::error("Erro ao agendar movimentacao de estoque do negocio {$negocio->codnegocio}: ", (array) $ret);
                return false;
            }
        } catch (\Throwable $th) {
            Log::error("Erro ao agendar movimentacao de estoque do negocio {$negocio->codnegocio}: {$th->getMessage()}");
            return false;
        }
        return true;
    }

    public static function fechar(Negocio $negocio, Pdv $pdv)
    {
        // inicia transacao no Banco
        DB::beginTransaction();

        // validacao de status
        if ($negocio->codnegociostatus != NegocioStatus::ABERTO) {
            throw new Exception('O Status do Negócio não permite Fechamento!', 1);
        }

        // validacao de itens informados
        if ($negocio->NegocioProdutoBarras()->whereNull('inativo')->count() == 0) {
            throw new Exception('Não foi informado nenhum produto neste negócio!', 1);
        }

        // validacoes de venda
        if ($negocio->NaturezaOperacao->venda == true) {

            // valida se tem CPF/CNPJ
            if (($negocio->valortotal >= 1000) && (empty($negocio->Pessoa->cnpj)) && (empty($negocio->cpf))) {
                throw new Exception('Obrigatório Identificar CPF para vendas acima de R$ 1.000,00!', 1);
            }
        }

        if ($negocio->NaturezaOperacao->financeiro == true) {

            //Calcula total pagamentos à vista e à prazo
            $valorPagamentos = 0;
            $valorPagamentosPrazo = 0;
            $valorLimiteCredito = 0;
            foreach ($negocio->NegocioFormaPagamentos as $nfp) {
                $valorPagamentos += $nfp->valortotal;
                if ($nfp->prazo && $nfp->parcelas > 1 && $negocio->codpessoa == 1) {
                    throw new Exception('Somente é permitido Parcelamento para Pessoas ou Empresas Cadastradas!', 1);
                }
                if ($nfp->FormaPagamento->boleto && $negocio->codpessoa == 1) {
                    throw new Exception('Somente é permitido Boleto para Pessoas ou Empresas Cadastradas!', 1);
                }
                if ($nfp->FormaPagamento->fechamento && $negocio->codpessoa == 1) {
                    throw new Exception('Somente é permitido Fechamento para Pessoas ou Empresas Cadastradas!', 1);
                }
                if (!$nfp->FormaPagamento->avista) {
                    $valorPagamentosPrazo += $nfp->valorpagamento;
                    if (!$nfp->FormaPagamento->entrega && !$nfp->FormaPagamento->pix) {
                        $valorLimiteCredito += $nfp->valorpagamento;
                    }
                }
            }

            //valida total pagamentos
            if (($negocio->valortotal - $valorPagamentos) >= 0.01) {
                $valorPagamentos = formataNumero($valorPagamentos, 2);
                $valorTotal = formataNumero($negocio->valortotal, 2);
                throw new Exception("O valor dos Pagamentos ({$valorPagamentos}) é inferior ao Total ({$valorTotal})!", 1);
            }

            if (($negocio->valortotal - $valorPagamentos) <= -0.01) {
                $valorPagamentos = formataNumero($valorPagamentosPrazo, 2);
                $valorTotal = formataNumero($negocio->valortotal, 2);
                throw new Exception("O valor à prazo ({$valorPagamentos}) é superior ao Total ({$valorTotal})!", 1);
            }

            // valida se tem limite de credito
            if (!PdvNegocioPrazoService::avaliaLimiteCredito($negocio->Pessoa, $valorLimiteCredito)) {
                throw new Exception('Solicite Liberação de Crédito ao Departamento Financeiro!', 1);
            }
        }

        // validacoes de transferencia
        if ($negocio->NaturezaOperacao->transferencia == true) {
            $mesmaEmpresa = false;
            foreach ($negocio->Pessoa->FilialS as $fil) {
                if ($fil->codempresa == $negocio->Filial->codempresa) {
                    $mesmaEmpresa = true;
                } else {
                    $mesmaEmpresa = false;
                }
            }
            if (!$mesmaEmpresa) {
                throw new Exception("A Pessoa destino precisa ser uma Filial!", 1);
            }
        }

        // marca negocio como fechado
        $negocio->codnegociostatus = NegocioStatus::FECHADO;
        if ($usuario = Auth::user()) {
            $negocio->codusuario = $usuario->codusuario;
        }
        $negocio->codpdv = $pdv->codpdv;
        $negocio->lancamento = Carbon::now();
        $negocio->save();

        // gera titulos do financeiro
        if ($negocio->NaturezaOperacao->financeiro) {
            $prazo = PdvNegocioPrazoService::gerarTitulos($negocio);
            $negocio->valoraprazo = $prazo;
            $negocio->valoravista = $negocio->valortotal - $prazo;
            $negocio->save();
            PdvNegocioPrazoService::baixarVales($negocio);
        } else {
            $negocio->valoraprazo = 0;
            $negocio->valoravista = 0;
            $negocio->save();
        }

        // salva transacao no banco de dados
        DB::commit();

        // busca dados atualizados no banco de dados
        $negocio = $negocio->fresh();

        // registra boletos se houver
        try {
            BoletoBbService::registrarPeloNegocio($negocio);
        } catch (\Throwable $th) {
        }

        // agenda movimentacao de estoque
        static::movimentarEstoque($negocio);

        // retorna
        return $negocio;
    }

    public static function cancelar(Negocio $negocio, Pdv $pdv, String $justificativa)
    {
        if (!in_array($negocio->codnegociostatus, [1, 2])) {
            throw new Exception("Status do Negócio Não Permite Cancelamento!", 1);
        }

        foreach ($negocio->NegocioProdutoBarraS as $npb) {
            foreach ($npb->NotaFiscalProdutoBarraS as $nfpb) {
                if (NotaFiscalService::isAtiva($nfpb->NotaFiscal)) {
                    throw new Exception("Negócio possui Nota Fiscal ativa!", 1);
                }
            }
        }

        foreach ($negocio->NegocioFormaPagamentoS as $nfp) {
            foreach ($nfp->TituloS as $tit) {
                if (!empty($tit->estornado)) {
                    continue;
                }
                TituloService::estornar($tit);
            }
        }
        PdvNegocioPrazoService::estornarBaixaVales($negocio);

        $negocio->codnegociostatus = NegocioStatus::CANCELADO;
        $negocio->justificativa = $justificativa;
        $negocio->save();
        static::movimentarEstoque($negocio);
        return $negocio;
    }
}
