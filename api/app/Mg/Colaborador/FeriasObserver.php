<?php

/**
 * Observer para o model Ferias.
 *
 * Registrar em AppServiceProvider ou EventServiceProvider:
 * Ferias::observe(FeriasObserver::class);
 */

namespace Mg\Colaborador;

use Carbon\Carbon;
use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Pessoa\Calendario\EventoCalendarioService;
use Mg\Pessoa\Calendario\GoogleCalendarService;

class FeriasObserver
{
    /**
     * Definição dos eventos vinculados às férias.
     * tipo => campo do model Ferias
     */
    protected array $eventos = [
        'FER_INI' => 'gozoinicio',
        'FER_FIM' => 'gozofim',
    ];

    /**
     * Antes de excluir Férias, remove os eventos do Google e do banco
     */
    public function deleting(Ferias $ferias): void
    {
        $eventos = EventoCalendario::where('codferias', $ferias->codferias)->get();

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
     * Após criar Férias, gera os eventos de calendário
     */
    public function created(Ferias $ferias): void
    {
        $this->sincronizarEventos($ferias);
    }

    /**
     * Após atualizar Férias, sincroniza os eventos de calendário
     */
    public function updated(Ferias $ferias): void
    {
        $this->sincronizarEventos($ferias);
    }

    /**
     * Percorre todos os tipos de evento e cria/atualiza/inativa conforme necessário
     */
    private function sincronizarEventos(Ferias $ferias): void
    {
        foreach ($this->eventos as $tipo => $campo) {
            $this->sincronizarEvento($ferias, $tipo, $ferias->{$campo});
        }
    }

    /**
     * Cria, atualiza ou inativa um EventoCalendario para o tipo informado
     */
    private function sincronizarEvento(Ferias $ferias, string $tipo, ?Carbon $data): void
    {
        $evento = EventoCalendario::where('codferias', $ferias->codferias)
            ->where('tipo', $tipo)
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
            && !$evento->inativo
            && $evento->status === 'SINC'
        ) {
            return;
        }

        // Criar ou atualizar o evento
        if (!$evento) {
            EventoCalendario::create([
                'codcolaborador' => $ferias->codcolaborador,
                'codferias' => $ferias->codferias,
                'tipo' => $tipo,
                'dataevento' => $data,
                'recorrente' => false,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        } else {
            $evento->update([
                'dataevento' => $data,
                'recorrente' => false,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        }
    }
}
