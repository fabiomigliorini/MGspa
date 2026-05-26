<?php

namespace Mg\Pessoa\Calendario;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncEventoCalendarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public EventoCalendario $evento;

    public function __construct(EventoCalendario $evento)
    {
        $this->evento = $evento;
        $this->afterCommit();
    }

    /**
     * Delega a sincronização para o EventoCalendarioService
     */
    public function handle(): void
    {
        EventoCalendarioService::sync($this->evento);
    }
}
