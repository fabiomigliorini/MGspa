<?php

namespace Mg\Requisicao;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Evento de um negocio ligado a requisicoes (sincronizado, fechado, cancelado,
// reaberto, entregue): reprocessa as requisicoes dele (TASK-55, doc-6).
class ProcessarRequisicaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $codnegocio;
    public string $evento;

    public function __construct(int $codnegocio, string $evento)
    {
        $this->codnegocio = $codnegocio;
        $this->evento = $evento;
    }

    public function handle(): void
    {
        RequisicaoProcessamentoService::processarEvento($this->codnegocio, $this->evento);
    }
}
