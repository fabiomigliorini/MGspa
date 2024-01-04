<?php

namespace Mg\Pdv;

use DB;
use Carbon\Carbon;

use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Titulo\Titulo;
use Mg\Portador\Portador;

class PdvNegocioPrazoService
{

    public static function avaliaLimiteCredito(Pessoa $pessoa, $valor)
    {
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
                MIN(vencimento) AS vencimento
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
        // percorre pagamentos
        foreach ($negocio->NegocioFormaPagamentos as $nfp) {

            // se a vista, continua
            if (!$nfp->FormaPagamento->avista) {
                continue;
            }

            // //se ja tem titulos gerados gera erro
            // if (count($nfp->Titulos) != 0) {
            //     $nfp->addError("codformapagamento", "Já existem Títulos gerados para a forma de pagamento, impossível gerar novos!");
            //     return false;
            // }
            $total = 0;

            // faz um looping para gerar duplicatas
            for ($i = 1; $i <= $nfp->FormaPagamento->parcelas; $i++) {

                //Joga diferença no último titulo gerado
                if ($i == $nfp->FormaPagamento->parcelas) {
                    $valor = $nfp->valorpagamento - $total;
                } else {
                    $valor = floor($nfp->valorpagamento / $nfp->FormaPagamento->parcelas);
                }
                $total += $valor;

                $titulo = new Titulo();
                $titulo->codnegocioformapagamento = $nfp->codnegocioformapagamento;
                $titulo->codfilial = $nfp->Negocio->codfilial;
                $titulo->codtipotitulo = $nfp->Negocio->NaturezaOperacao->codtipotitulo;
                $titulo->codcontacontabil = $nfp->Negocio->NaturezaOperacao->codcontacontabil;
                $titulo->valor = $valor;
                $titulo->boleto = false;
                $titulo->codpessoa = $nfp->Negocio->codpessoa;
                $titulo->numero = "N" . str_pad($nfp->codnegocio, 8, "0", STR_PAD_LEFT) . "-$i/{$nfp->FormaPagamento->parcelas}";
                $titulo->emissao = Cabon::now();
                $titulo->transacao = Cabon::now();
                $titulo->vencimento = Carbon::now()->addDays($i * $nfp->FormaPagamento->diasentreparcelas);
                $titulo->vencimentooriginal = $titulo->vencimento;
                $titulo->gerencial = true;
                $titulo->codportador = Portador::CARTEIRA;

                //se deu erro ao salvar titulo aborta
                if (!$titulo->save()) {
                    throw new Exception('Falha ao Gerar Títulos do Negócio!', 1);
                }
            }
        }

        return true;
    }
}
