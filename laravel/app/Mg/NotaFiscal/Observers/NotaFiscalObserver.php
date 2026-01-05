<?php

namespace Mg\NotaFiscal\Observers;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;

class NotaFiscalObserver
{
    /**
     * Campos que afetam o status da nota
     */
    private const CAMPOS_STATUS = [
        'emitida',
        'numero',
        'nfeautorizacao',
        'nfecancelamento',
        'nfeinutilizacao'
    ];

    /**
     * Campos que exigem recálculo de tributação dos itens
     */
    private const CAMPOS_TRIBUTACAO = [
        'codnaturezaoperacao',
        'codpessoa',
        'codfilial'
    ];

    /**
     * Handle the NotaFiscal "creating" event.
     * Atualiza o status antes de criar
     */
    public function creating(NotaFiscal $notaFiscal): void
    {
        $notaFiscal->status = NotaFiscalService::calcularStatus($notaFiscal);
    }

    /**
     * Handle the NotaFiscal "updating" event.
     * Atualiza o status antes de salvar se os campos relevantes mudaram
     */
    public function updating(NotaFiscal $notaFiscal): void
    {
        // Atualiza status se campos relevantes mudaram
        foreach (self::CAMPOS_STATUS as $campo) {
            if ($notaFiscal->isDirty($campo)) {
                $notaFiscal->status = NotaFiscalService::calcularStatus($notaFiscal);
                break;
            }
        }
    }

    /**
     * Handle the NotaFiscal "updated" event.
     * Recalcula tributação dos itens se campos relevantes mudaram
     */
    public function updated(NotaFiscal $notaFiscal): void
    {
        // Verifica se algum campo que afeta tributação foi alterado
        $recalcular = false;
        foreach (self::CAMPOS_TRIBUTACAO as $campo) {
            if ($notaFiscal->wasChanged($campo)) {
                $recalcular = true;
                break;
            }
        }
        if ($recalcular) {
            NotaFiscalService::recalcularTributacao($notaFiscal);
        }
    }
}
