<?php

namespace Mg\Titulo;

use Carbon\Carbon;

/**
 * Porta os helpers `adicionaMultaJurosDesconto` / `adicionaMovimento`
 * do legado MGsis para o backend MGspa, sem transação interna
 * (transação fica a cargo do Service/Controller chamador).
 */
class MovimentoTituloHelper
{
    public static function adicionarMultaJurosDesconto(
        Titulo $titulo,
        float $multa = 0,
        float $juros = 0,
        float $desconto = 0,
        ?string $transacao = null,
        ?int $codportador = null,
        ?int $codtituloagrupamento = null,
        ?int $codliquidacaotitulo = null
    ): void {
        // juros e multa aumentam o título; desconto diminui
        $sinal = $titulo->ehReceber() ? 1 : -1;
        $vinculos = self::vinculos($transacao, $codportador, $codtituloagrupamento, $codliquidacaotitulo);

        if ($juros > 0) {
            MovimentoTituloService::lancar($titulo, MovimentoTituloService::TIPO_JUROS, $sinal * $juros, $vinculos);
        }

        if ($multa > 0) {
            MovimentoTituloService::lancar($titulo, MovimentoTituloService::TIPO_MULTA, $sinal * $multa, $vinculos);
        }

        if ($desconto > 0) {
            MovimentoTituloService::lancar($titulo, MovimentoTituloService::TIPO_DESCONTO, -1 * $sinal * $desconto, $vinculos);
        }
    }

    public static function liquidar(
        Titulo $titulo,
        float $total,
        ?string $transacao = null,
        ?int $codportador = null,
        ?int $codtituloagrupamento = null,
        ?int $codliquidacaotitulo = null,
        int $tipo = MovimentoTituloService::TIPO_LIQUIDACAO
    ): void {
        if ($total <= 0) return;
        $sinal = $titulo->ehReceber() ? 1 : -1;
        MovimentoTituloService::lancar(
            $titulo,
            $tipo,
            -1 * $sinal * $total,
            self::vinculos($transacao, $codportador, $codtituloagrupamento, $codliquidacaotitulo)
        );
    }

    private static function vinculos(
        ?string $transacao,
        ?int $codportador,
        ?int $codtituloagrupamento,
        ?int $codliquidacaotitulo
    ): array {
        return [
            'transacao'            => $transacao ? Carbon::parse($transacao)->format('Y-m-d') : Carbon::today()->format('Y-m-d'),
            'codtituloagrupamento' => $codtituloagrupamento,
            'codliquidacaotitulo'  => $codliquidacaotitulo,
            'codportador'          => $codportador,
        ];
    }
}
