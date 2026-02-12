<?php

/**
 * Observer para o model EventoCalendario.
 *
 * Registrar em AppServiceProvider ou EventServiceProvider:
 * EventoCalendario::observe(EventoCalendarioObserver::class);
 */

namespace Mg\Pessoa\Calendario;

class EventoCalendarioObserver
{
    /**
     * Campos que NÃO devem disparar sincronização quando alterados sozinhos.
     * Evita loop infinito quando o Job atualiza status/googleeventid.
     */
    protected array $camposIgnorados = [
        'status',
        'googleeventid',
    ];

    /**
     * Após criar um EventoCalendario, dispara sincronização
     */
    public function created(EventoCalendario $evento): void
    {
        dispatch(new SyncEventoCalendarioJob($evento));
    }

    /**
     * Após atualizar um EventoCalendario, dispara sincronização
     * somente se houve mudança em campos relevantes
     */
    public function updated(EventoCalendario $evento): void
    {
        // Se apenas campos ignorados mudaram, não disparar o job
        $mudancas = array_keys($evento->getChanges());
        $mudancasRelevantes = array_diff($mudancas, $this->camposIgnorados);

        if (empty($mudancasRelevantes)) {
            return;
        }

        dispatch(new SyncEventoCalendarioJob($evento));
    }

    /**
     * Após deletar um EventoCalendario, dispara sincronização
     * para remover o evento do Google Calendar
     */
    public function deleted(EventoCalendario $evento): void
    {
        if (!$evento->googleeventid) {
            return;
        }

        try {
            app(GoogleCalendarService::class)
                ->deleteEvent($evento->googleeventid);
        } catch (\Throwable $e) {
            // opcional: logar erro
            report($e);
        }
    }
}
