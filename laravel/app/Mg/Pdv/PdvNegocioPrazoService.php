<?php

namespace Mg\Pdv;

use DB;
use Carbon\Carbon;
use \Exception;

use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Titulo\Titulo;
use Mg\Portador\Portador;
use Mg\Titulo\TipoTituloService;

class PdvNegocioPrazoService
{

    public static function avaliaLimiteCredito(Pessoa $pessoa, $valor)
    {
        if ($valor == 0) {
            return true;
        }

        // se esta com o credito marcado como bloqueado
        if ($pessoa->creditobloqueado) {
            return false;
        }

        // busca no banco total dos titulos
        $aberto = static::emAberto($pessoa);

        // valida total
        $total = $aberto->saldo + $valor;
        if ((!empty($pessoa->credito)) && (($pessoa->credito * 1.05) < $total)) {
            return false;
        }

        //verifica o atraso
        if (!empty($aberto->vencimento)) {
            $atraso = Carbon::parse($aberto->vencimento)->diffInDays(Carbon::now());
            if ($atraso > $pessoa->toleranciaatraso) {
                return false;
            }
        }

        return true;
    }

    public static function emAberto(Pessoa $pessoa)
    {
        $sql = '
            SELECT SUM(saldo) AS saldo,
                MIN(vencimento) AS vencimento,
                COUNT(codtitulo) as quantidade
            FROM tbltitulo
            WHERE codpessoa = :codpessoa
            AND saldo <> 0
            AND debito > 0
        ';
        $tot = DB::select($sql, [
            'codpessoa' => $pessoa->codpessoa
        ]);
        return $tot[0];
    }


    public static function gerarTitulos(Negocio $negocio)
    {
        // busca pagamentos a prazo
        $nfps = $negocio
            ->NegocioFormaPagamentos()
            ->where('avista', false)
            ->orderBy('codnegocioformapagamento')
            ->get();

        // verifica se precisa de sufixo
        // quando tem mais de um pagamento a Prazo
        $qtdPrazo = $nfps->count();
        if ($qtdPrazo > 1) {
            $sufixo = '-A';
        } else {
            $sufixo = '';
        }
        $sufixos = [$sufixo];

        $totalTitulos = 0;
        // Percorre todas as formas
        foreach ($nfps as $nfp) {

            // inicializa um acumulador para controlar quanto deve ser a diferenca da ultima parcela
            $total = 0;
            $parcelas = ($nfp->parcelas > 0) ? $nfp->parcelas : 1;

            // faz um looping para gerar duplicatas
            for ($i = 1; $i <= $parcelas; $i++) {

                // usa o valor da parcela informado ou 
                // joga diferença do total no último titulo gerado
                if ($i == $parcelas) {
                    $valor = $nfp->valortotal - $total;
                } else {
                    $valor = $nfp->valorparcela;
                }
                $total += $valor;
                $totalTitulos += $valor;

                // calcula data de vencimento
                if ($nfp->FormaPagamento->fechamento) {
                    $vencimento = Carbon::now()->addMonths($i)->addDays(7)->endOfMonth();
                } else {
                    $vencimento = Carbon::now()->addMonths($i);
                }

                // calcula o tipo de titulo
                if ($nfp->FormaPagamento->pix) {
                    if ($nfp->Negocio->codoperacao == 2) {
                        $tipo = TipoTituloService::TIPO_PIX_RECEBER;
                    } else {
                        $tipo = TipoTituloService::TIPO_PIX_PAGAR;
                    }
                } elseif ($nfp->FormaPagamento->entrega) {
                    if ($nfp->Negocio->codoperacao == 2) {
                        $tipo = TipoTituloService::TIPO_ENTREGA_RECEBER;
                    } else {
                        $tipo = TipoTituloService::TIPO_ENTREGA_PAGAR;
                    }
                } else {
                    $tipo = $nfp->Negocio->NaturezaOperacao->codtipotitulo;
                }

                // Cria Registro de Titulo
                $titulo = new Titulo();
                $titulo->codnegocioformapagamento = $nfp->codnegocioformapagamento;
                $titulo->codfilial = $nfp->Negocio->codfilial;
                $titulo->codtipotitulo = $tipo;
                $titulo->codcontacontabil = $nfp->Negocio->NaturezaOperacao->codcontacontabil;
                if ($nfp->Negocio->codoperacao == 2) {
                    $titulo->debito = $valor;
                } else {
                    $titulo->credito = $valor;
                }
                $titulo->boleto = false;
                $titulo->codpessoa = $nfp->Negocio->codpessoa;
                $titulo->numero = "N" . str_pad($nfp->codnegocio, 8, "0", STR_PAD_LEFT) . "$sufixo-$i/{$parcelas}";
                $titulo->emissao = Carbon::now();
                $titulo->transacao = $titulo->emissao;
                $titulo->sistema = $titulo->emissao;
                $titulo->vencimento = $vencimento;
                $titulo->vencimentooriginal = $titulo->vencimento;
                $titulo->gerencial = true;
                $titulo->codportador = Portador::CARTEIRA;

                //se deu erro ao salvar titulo aborta
                if (!$titulo->save()) {
                    throw new Exception('Falha ao Gerar Títulos do Negócio!', 1);
                }
            }

            // monta sufixo da proxima forma de pagamento
            if ($qtdPrazo > 1) {
                $sufixo = '-' . chr(ord(substr($sufixo, -1)) + 1);
                $sufixos[] = $sufixo;
            }
        }

        return $totalTitulos;
    }
}
