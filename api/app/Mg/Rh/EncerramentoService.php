<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;

class EncerramentoService
{
    public static function encerrar(int $codperiodocolaborador): PeriodoColaborador
    {
        $pc = PeriodoColaborador::with(['Colaborador', 'Periodo'])->findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ABERTO) {
            throw new \Exception('Somente colaboradores com status A (aberto) podem ser encerrados.');
        }

        // Recalcula antes de encerrar
        CalculoRubricaService::calcularColaborador($codperiodocolaborador);
        $pc->refresh();

        // Encerra SEM gerar título no contas a pagar. A remuneração variável fica
        // apenas no valortotal (valor calculado); a entrega (Bee/dinheiro) e o
        // desconto em folha são registrados como eventos de acerto
        // (tblperiodocolaboradoracerto), que baixam os vales via movimento.
        $pc->status = PeriodoService::STATUS_COLABORADOR_ENCERRADO;
        $pc->encerramento = Carbon::now();
        $pc->codtitulo = null;
        $pc->save();

        return $pc;
    }

    public static function estornar(int $codperiodocolaborador): PeriodoColaborador
    {
        $pc = PeriodoColaborador::findOrFail($codperiodocolaborador);

        if ($pc->status !== PeriodoService::STATUS_COLABORADOR_ENCERRADO) {
            throw new \Exception('Somente colaboradores com status E (encerrado) podem ser estornados.');
        }

        // Reabrir apenas destrava (rubricas + acertos voltam a ser editáveis). Os
        // eventos de acerto já lançados permanecem — nada é estornado aqui.
        // Reabre o colaborador
        $pc->status = PeriodoService::STATUS_COLABORADOR_ABERTO;
        $pc->codtitulo = null;
        $pc->encerramento = null;
        $pc->save();

        return $pc;
    }
}
