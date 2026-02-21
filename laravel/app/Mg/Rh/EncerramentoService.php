<?php

namespace Mg\Rh;

use Carbon\Carbon;
use Mg\Titulo\Titulo;

class EncerramentoService
{
    const CODTIPOTITULO_VARIAVEL = 952;
    const CODCONTACONTABIL = 360;

    public static function encerrar(int $codperiodocolaborador): PeriodoColaborador
    {
        $pc = PeriodoColaborador::with(['Colaborador', 'Periodo'])->findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ABERTO) {
            throw new \Exception('Somente colaboradores com status A (aberto) podem ser encerrados.');
        }

        // Recalcula antes de encerrar
        CalculoRubricaService::calcularColaborador($codperiodocolaborador);
        $pc->refresh();

        $agora = Carbon::now();

        // Se valortotal = 0, encerra sem título
        if ($pc->valortotal == 0) {
            $pc->status = PeriodoService::STATUS_COLABORADOR_ENCERRADO;
            $pc->encerramento = $agora;
            $pc->codtitulo = null;
            $pc->save();
            return $pc;
        }

        // Gera título
        $debito = $pc->valortotal > 0 ? $pc->valortotal : 0;
        $credito = $pc->valortotal < 0 ? abs($pc->valortotal) : 0;

        $titulo = new Titulo([
            'codtipotitulo' => self::CODTIPOTITULO_VARIAVEL,
            'codfilial' => $pc->Colaborador->codfilial,
            'codpessoa' => $pc->Colaborador->codpessoa,
            'codcontacontabil' => self::CODCONTACONTABIL,
            'numero' => "RH-{$pc->codperiodo}-{$pc->codcolaborador}",
            'emissao' => $agora,
            'vencimento' => $agora,
            'transacao' => $agora,
            'sistema' => $agora,
            'debito' => $debito,
            'credito' => $credito,
            'debitototal' => $debito,
            'creditototal' => $credito,
            'saldo' => abs($pc->valortotal),
            'debitosaldo' => $debito,
            'creditosaldo' => $credito,
            'gerencial' => true,
            'observacao' => "Remuneração variável — Período {$pc->Periodo->periodoinicial->format('d/m/Y')} a {$pc->Periodo->periodofinal->format('d/m/Y')}",
        ]);
        $titulo->save();

        // Atualiza PeriodoColaborador
        $pc->status = PeriodoService::STATUS_COLABORADOR_ENCERRADO;
        $pc->codtitulo = $titulo->codtitulo;
        $pc->encerramento = $agora;
        $pc->save();

        return $pc;
    }

    public static function estornar(int $codperiodocolaborador): PeriodoColaborador
    {
        $pc = PeriodoColaborador::findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ENCERRADO) {
            throw new \Exception('Somente colaboradores com status E (encerrado) podem ser estornados.');
        }

        // Estorna título se existir
        if ($pc->codtitulo) {
            $titulo = Titulo::findOrFail($pc->codtitulo);
            $titulo->estornado = Carbon::now();
            $titulo->saldo = 0;
            $titulo->debitosaldo = 0;
            $titulo->creditosaldo = 0;
            $titulo->save();
        }

        // Reabre o colaborador
        $pc->status = PeriodoService::STATUS_COLABORADOR_ABERTO;
        $pc->codtitulo = null;
        $pc->encerramento = null;
        $pc->save();

        return $pc;
    }

    public static function encerrarValorZero(int $codperiodocolaborador): PeriodoColaborador
    {
        $pc = PeriodoColaborador::findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ABERTO) {
            throw new \Exception('Somente colaboradores com status A (aberto) podem ser encerrados.');
        }

        CalculoRubricaService::calcularColaborador($codperiodocolaborador);
        $pc->refresh();

        $pc->status = PeriodoService::STATUS_COLABORADOR_ENCERRADO;
        $pc->encerramento = Carbon::now();
        $pc->codtitulo = null;
        $pc->save();

        return $pc;
    }
}
