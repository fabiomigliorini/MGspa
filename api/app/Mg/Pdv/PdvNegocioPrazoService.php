<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use \Exception;

use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

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
            $atraso = Carbon::parse($aberto->vencimento)->diffInDays(Carbon::now(), false);
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
            AND valor > 0
        ';
        $tot = DB::select($sql, [
            'codpessoa' => $pessoa->codpessoa
        ]);
        return $tot[0];
    }


    // vales consumidos como pagamento (meio vale com o titulo do vale)
    public static function pagamentosVale(Negocio $negocio)
    {
        return $negocio->PagamentoS()
            ->whereNotNull('codtitulo')
            ->where('meio', PagamentoService::MEIO_VALE)
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->get();
    }

    // Idempotente (TASK-30): o vale que ja' tem a baixa ativa fica como esta';
    // o reativado na venda reaberta (baixa estornada) ganha baixa nova
    public static function baixarVales(Negocio $negocio)
    {
        foreach (static::pagamentosVale($negocio) as $pag) {
            if (static::amortizacaoAtiva($pag)) {
                continue;
            }
            MovimentoTituloService::lancar(
                $pag->Titulo,
                MovimentoTituloService::TIPO_AMORTIZACAO,
                ($negocio->codoperacao == 2) ? $pag->principal : -$pag->principal,
                [],
                [
                    'codtitulo' => $pag->codtitulo,
                    'codpagamento' => $pag->codpagamento,
                    'codportador' => $pag->Titulo->codportador,
                    'transacao' => $negocio->lancamento,
                ]
            );
        }
    }

    // a baixa do vale por este pagamento que ainda nao foi estornada
    public static function amortizacaoAtiva(Pagamento $pag): ?MovimentoTitulo
    {
        return static::amortizacoesAtivas($pag)->first();
    }

    private static function amortizacoesAtivas(Pagamento $pag)
    {
        if (empty($pag->codtitulo) || $pag->meio != PagamentoService::MEIO_VALE) {
            return collect();
        }
        return MovimentoTitulo::where('codpagamento', $pag->codpagamento)
            ->where('codtitulo', $pag->codtitulo)
            ->where('codtipomovimentotitulo', MovimentoTituloService::TIPO_AMORTIZACAO)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('tblmovimentotitulo as e')
                    ->whereColumn('e.codmovimentotituloestorno', 'tblmovimentotitulo.codmovimentotitulo');
            })
            ->orderBy('codmovimentotitulo')
            ->get();
    }

    // roda antes de cancelar os pagamentos: por isso le' todos os do vale
    public static function estornarBaixaVales(Negocio $negocio)
    {
        $pags = $negocio->PagamentoS()
            ->whereNotNull('codtitulo')
            ->where('meio', PagamentoService::MEIO_VALE)
            ->get();
        foreach ($pags as $pag) {
            static::estornarBaixaVale($pag);
        }
    }

    // estorna a baixa ativa do vale deste pagamento (idempotente: o estorno
    // e' unico por baixa estornada)
    public static function estornarBaixaVale(Pagamento $pag)
    {
        foreach (static::amortizacoesAtivas($pag) as $movOriginal) {
            MovimentoTituloService::lancar(
                $pag->Titulo,
                MovimentoTituloService::TIPO_ESTORNO_AMORTIZACAO,
                -1 * (float) $movOriginal->principal,
                ['total' => -1 * (float) $movOriginal->total],
                [
                    'codtitulo' => $pag->codtitulo,
                    'codpagamento' => $pag->codpagamento,
                    'codmovimentotituloestorno' => $movOriginal->codmovimentotitulo,
                    'codportador' => $movOriginal->codportador,
                    'transacao' => $movOriginal->transacao,
                ],
                ['codmovimentotituloestorno']
            );
        }
    }
}
