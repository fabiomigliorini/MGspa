<?php

/**
 * Observer para o model Dependente.
 *
 * Registrar em AppServiceProvider ou EventServiceProvider:
 * Dependente::observe(DependenteObserver::class);
 */

namespace Mg\Pessoa;

use Carbon\Carbon;
use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Pessoa\Calendario\EventoCalendarioService;
use Mg\Pessoa\Calendario\GoogleCalendarService;

class DependenteObserver
{
    /**
     * Antes de excluir um Dependente, remove os eventos do Google e do banco
     */
    public function deleting(Dependente $dependente): void
    {
        $eventos = EventoCalendario::where('coddependente', $dependente->coddependente)->get();

        foreach ($eventos as $evento) {
            if ($evento->googleeventid) {
                try {
                    $calendarId = EventoCalendarioService::resolverCalendarId($evento->tipo);
                    app(GoogleCalendarService::class)->deleteEvent($calendarId, $evento->googleeventid);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $evento->delete();
        }
    }

    /**
     * Após criar um Dependente, gera o evento de aniversário
     */
    public function created(Dependente $dependente): void
    {
        $this->sincronizarAniversario($dependente);
    }

    /**
     * Após atualizar um Dependente, sincroniza o evento de aniversário
     */
    public function updated(Dependente $dependente): void
    {
        $this->sincronizarAniversario($dependente);
    }

    /**
     * Sincroniza o evento ANV_DEP com base em Pessoa->nascimento
     */
    private function sincronizarAniversario(Dependente $dependente): void
    {
        $data = $dependente->Pessoa?->nascimento;

        $evento = EventoCalendario::where('coddependente', $dependente->coddependente)
            ->where('tipo', 'ANV_DEP')
            ->first();

        // Se a data não existe, inativar o evento existente
        if (!$data) {
            if ($evento && !$evento->inativo) {
                $evento->update([
                    'inativo' => Carbon::now(),
                    'status' => 'PEND',
                ]);
            }
            return;
        }

        // Se nada mudou, não fazer nada
        if (
            $evento
            && $evento->dataevento?->equalTo($data)
            && $evento->recorrente === true
            && !$evento->inativo
            && $evento->status === 'SINC'
        ) {
            return;
        }

        // Criar ou atualizar o evento
        if (!$evento) {
            EventoCalendario::create([
                'coddependente' => $dependente->coddependente,
                'tipo' => 'ANV_DEP',
                'dataevento' => $data,
                'recorrente' => true,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        } else {
            $evento->update([
                'dataevento' => $data,
                'recorrente' => true,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        }
    }
}
