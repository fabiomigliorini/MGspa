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
        $operacao = self::operacao($titulo);

        if ($juros > 0) {
            self::movimentar(
                $titulo,
                MovimentoTituloService::TIPO_JUROS,
                $operacao === 'DB' ? $juros : 0,
                $operacao === 'CR' ? $juros : 0,
                $transacao,
                $codportador,
                $codtituloagrupamento,
                $codliquidacaotitulo
            );
        }

        if ($multa > 0) {
            self::movimentar(
                $titulo,
                MovimentoTituloService::TIPO_MULTA,
                $operacao === 'DB' ? $multa : 0,
                $operacao === 'CR' ? $multa : 0,
                $transacao,
                $codportador,
                $codtituloagrupamento,
                $codliquidacaotitulo
            );
        }

        if ($desconto > 0) {
            self::movimentar(
                $titulo,
                MovimentoTituloService::TIPO_DESCONTO,
                $operacao === 'CR' ? $desconto : 0,
                $operacao === 'DB' ? $desconto : 0,
                $transacao,
                $codportador,
                $codtituloagrupamento,
                $codliquidacaotitulo
            );
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
        $operacao = self::operacao($titulo);
        self::movimentar(
            $titulo,
            $tipo,
            $operacao === 'CR' ? $total : 0,
            $operacao === 'DB' ? $total : 0,
            $transacao,
            $codportador,
            $codtituloagrupamento,
            $codliquidacaotitulo
        );
    }

    public static function operacao(Titulo $titulo): string
    {
        $saldo = (float)$titulo->saldo;
        $credito = (float)$titulo->credito;
        $debito = (float)$titulo->debito;
        return ($saldo < 0 || $credito > $debito) ? 'CR' : 'DB';
    }

    private static function movimentar(
        Titulo $titulo,
        int $tipo,
        float $debito,
        float $credito,
        ?string $transacao,
        ?int $codportador,
        ?int $codtituloagrupamento,
        ?int $codliquidacaotitulo
    ): void {
        $mov = new MovimentoTitulo([
            'codtitulo'              => $titulo->codtitulo,
            'codtipomovimentotitulo' => $tipo,
            'debito'                 => $debito,
            'credito'                => $credito,
            'transacao'              => $transacao ? Carbon::parse($transacao)->format('Y-m-d') : Carbon::today()->format('Y-m-d'),
            'codtituloagrupamento'   => $codtituloagrupamento,
            'codliquidacaotitulo'    => $codliquidacaotitulo,
            'codportador'            => $codportador,
            'sistema'                => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $mov->save();
    }
}
