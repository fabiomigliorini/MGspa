<?php

namespace Mg\Titulo;

use Carbon\Carbon;

/**
 * Baixa de título pela liquidação e pelo agrupamento, sem transação interna
 * (transação fica a cargo do Service/Controller chamador).
 */
class MovimentoTituloHelper
{
    /**
     * Baixa o título numa linha só: $total é o que foi pago (ou levado ao
     * agrupamento), sem sinal; juros e multa aumentam o que se baixa do
     * título, desconto diminui. O principal que sai do saldo é
     * total - juros - multa + desconto.
     */
    public static function liquidar(
        Titulo $titulo,
        float $total,
        float $juros = 0,
        float $multa = 0,
        float $desconto = 0,
        ?string $transacao = null,
        ?int $codportador = null,
        ?int $codtituloagrupamento = null,
        ?int $codliquidacaotitulo = null,
        int $tipo = MovimentoTituloService::TIPO_LIQUIDACAO
    ): void {
        $principal = round($total - $juros - $multa + $desconto, 2);
        if ($total <= 0 && $principal == 0) return;

        // baixa de título a receber diminui o saldo: sinal negativo
        $sinal = $titulo->ehReceber() ? -1 : 1;
        MovimentoTituloService::lancar(
            $titulo,
            $tipo,
            $sinal * $principal,
            [
                'juros'    => $juros,
                'multa'    => $multa,
                'desconto' => $desconto,
                'total'    => $sinal * $total,
            ],
            [
                'transacao'            => $transacao ? Carbon::parse($transacao)->format('Y-m-d') : Carbon::today()->format('Y-m-d'),
                'codtituloagrupamento' => $codtituloagrupamento,
                'codliquidacaotitulo'  => $codliquidacaotitulo,
                'codportador'          => $codportador,
            ]
        );
    }
}
