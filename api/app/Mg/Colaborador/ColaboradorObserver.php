<?php

/**
 * Observer para o model Colaborador.
 *
 * Registrar em AppServiceProvider ou EventServiceProvider:
 * Colaborador::observe(ColaboradorObserver::class);
 */

namespace Mg\Colaborador;

use Carbon\Carbon;
use Mg\Pessoa\Calendario\EventoCalendario;
use Mg\Pessoa\Calendario\EventoCalendarioService;
use Mg\Pessoa\Calendario\GoogleCalendarService;
use Mg\Pessoa\Dependente;
use Mg\Pessoa\DependenteObserver;

class ColaboradorObserver
{
    /**
     * Definição dos eventos vinculados ao colaborador.
     * tipo => [campo do colaborador, recorrente, via Pessoa]
     */
    protected array $eventos = [
        'ANV_COL' => ['campo' => 'nascimento', 'recorrente' => true,  'pessoa' => true],
        'ANV_EMP' => ['campo' => 'contratacao', 'recorrente' => true,  'pessoa' => false, 'addYears' => 1],
        'EXP_FIM' => ['campo' => 'experiencia', 'recorrente' => false, 'pessoa' => false],
        'EXP_REN' => ['campo' => 'renovacaoexperiencia', 'recorrente' => false, 'pessoa' => false],
    ];

    /**
     * Antes de excluir um Colaborador, remove os eventos do Google e do banco
     */
    public function deleting(Colaborador $colaborador): void
    {
        $eventos = EventoCalendario::where('codcolaborador', $colaborador->codcolaborador)->get();

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
     * Após criar um Colaborador, gera todos os eventos
     */
    public function created(Colaborador $colaborador): void
    {
        $this->sincronizarEventos($colaborador);
    }

    /**
     * Após atualizar um Colaborador, sincroniza os eventos
     */
    public function updated(Colaborador $colaborador): void
    {
        $this->sincronizarEventos($colaborador);
    }

    /**
     * Percorre todos os tipos de evento e cria/atualiza/inativa conforme necessário
     */
    private function sincronizarEventos(Colaborador $colaborador): void
    {
        // Se rescisão preenchida, inativar todos os eventos
        if ($colaborador->rescisao) {
            $this->inativarTodosEventos($colaborador);
            return;
        }

        foreach ($this->eventos as $tipo => $config) {
            $data = $this->resolverData($colaborador, $config);
            $this->sincronizarEvento($colaborador, $tipo, $data, $config['recorrente']);
        }

        // Reativar eventos de férias
        $feriasObserver = new FeriasObserver();
        foreach ($colaborador->FeriasS as $ferias) {
            $feriasObserver->created($ferias);
        }

        // Reativar eventos de dependentes
        $dependenteObserver = new DependenteObserver();
        $dependentes = Dependente::where('codpessoaresponsavel', $colaborador->codpessoa)->get();
        foreach ($dependentes as $dependente) {
            $dependenteObserver->created($dependente);
        }
    }

    /**
     * Inativa todos os eventos do colaborador e de seus dependentes
     */
    private function inativarTodosEventos(Colaborador $colaborador): void
    {
        // Eventos diretos do colaborador (ANV_COL, ANV_EMP, EXP_FIM, EXP_REN, FER_INI, FER_FIM)
        $eventos = EventoCalendario::where('codcolaborador', $colaborador->codcolaborador)
            ->whereNull('inativo')
            ->get();

        foreach ($eventos as $evento) {
            $evento->update([
                'inativo' => Carbon::now(),
                'status' => 'PEND',
            ]);
        }

        // Eventos dos dependentes (ANV_DEP)
        $codsDependentes = Dependente::where('codpessoaresponsavel', $colaborador->codpessoa)
            ->pluck('coddependente');

        if ($codsDependentes->isNotEmpty()) {
            $eventosDep = EventoCalendario::whereIn('coddependente', $codsDependentes)
                ->whereNull('inativo')
                ->get();

            foreach ($eventosDep as $evento) {
                $evento->update([
                    'inativo' => Carbon::now(),
                    'status' => 'PEND',
                ]);
            }
        }
    }

    /**
     * Resolve a data do evento a partir do colaborador ou da pessoa vinculada
     */
    private function resolverData(Colaborador $colaborador, array $config): ?Carbon
    {
        if ($config['pessoa']) {
            $data = $colaborador->Pessoa?->{$config['campo']};
        } else {
            $data = $colaborador->{$config['campo']};
        }

        if ($data && !empty($config['addYears'])) {
            $data = $data->copy()->addYears($config['addYears']);
        }

        return $data;
    }

    /**
     * Cria, atualiza ou inativa um EventoCalendario para o tipo informado
     */
    private function sincronizarEvento(Colaborador $colaborador, string $tipo, ?Carbon $data, bool $recorrente): void
    {
        $evento = EventoCalendario::where('codcolaborador', $colaborador->codcolaborador)
            ->where('tipo', $tipo)
            ->first();

        // Se a data não existe mais, inativar o evento existente
        if (!$data) {
            if ($evento && !$evento->inativo) {
                $evento->update([
                    'inativo' => Carbon::now(),
                    'status' => 'PEND',
                ]);
            }
            return;
        }

        if (
            $evento
            && $evento->dataevento?->equalTo($data)
            && $evento->recorrente === $recorrente
            && !$evento->inativo
            && $evento->status === 'SINC'
        ) {
            return;
        }

        // Criar ou atualizar o evento
        if (!$evento) {
            EventoCalendario::create([
                'codcolaborador' => $colaborador->codcolaborador,
                'tipo' => $tipo,
                'dataevento' => $data,
                'recorrente' => $recorrente,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        } else {
            $evento->update([
                'dataevento' => $data,
                'recorrente' => $recorrente,
                'status' => 'PEND',
                'inativo' => null,
            ]);
        }
    }
}
