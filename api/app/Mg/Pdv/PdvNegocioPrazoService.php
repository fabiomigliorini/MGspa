<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use \Exception;

use Mg\Pessoa\Pessoa;
use Mg\Negocio\Negocio;
use Mg\Titulo\MovimentoTituloService;
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

    public static function baixarVales(Negocio $negocio)
    {
        foreach (static::pagamentosVale($negocio) as $pag) {
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
                ],
                ['codtitulo', 'codpagamento']
            );
        }
    }

    // roda antes de cancelar os pagamentos: por isso le' todos os do vale
    public static function estornarBaixaVales(Negocio $negocio)
    {
        $pags = $negocio->PagamentoS()
            ->whereNotNull('codtitulo')
            ->where('meio', PagamentoService::MEIO_VALE)
            ->get();
        foreach ($pags as $pag) {
            foreach ($pag->MovimentoTituloS as $movOriginal) {
                // so a amortizacao e' estornada; o proprio estorno tambem esta' na lista
                if ($movOriginal->codtipomovimentotitulo != MovimentoTituloService::TIPO_AMORTIZACAO) {
                    continue;
                }
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
                    ['codtitulo', 'codpagamento']
                );
            }
        }
    }
}
