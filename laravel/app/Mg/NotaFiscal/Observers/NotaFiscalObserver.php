<?php

namespace Mg\NotaFiscal\Observers;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;

class NotaFiscalObserver
{
    /**
     * Handle the NotaFiscal "creating" event.
     * Atualiza o status antes de criar
     */
    public function creating(NotaFiscal $notaFiscal)
    {
        $notaFiscal->status = NotaFiscalService::calcularStatus($notaFiscal);
    }

    /**
     * Handle the NotaFiscal "updating" event.
     * Atualiza o status antes de salvar se os campos relevantes mudaram
     */
    public function updating(NotaFiscal $notaFiscal)
    {
        // Campos que afetam o status
        $camposRelevantes = ['emitida', 'numero', 'nfeautorizacao', 'nfecancelamento', 'nfeinutilizacao'];

        // Verifica se algum campo relevante foi alterado
        foreach ($camposRelevantes as $campo) {
            if ($notaFiscal->isDirty($campo)) {
                $notaFiscal->status = NotaFiscalService::calcularStatus($notaFiscal);
                break;
            }
        }
    }
}
