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

class ColaboradorObserver
{
    /**
     * Definição dos eventos vinculados ao colaborador.
     * tipo => [campo do colaborador, recorrente, via Pessoa]
     */
    protected array $eventos = [
        'ANV_COL' => ['campo' => 'nascimento', 'recorrente' => true,  'pessoa' => true],
        'ANV_EMP' => ['campo' => 'contratacao', 'recorrente' => true,  'pessoa' => false],
        'EXP_FIM' => ['campo' => 'experiencia', 'recorrente' => false, 'pessoa' => false],
        'EXP_REN' => ['campo' => 'renovacaoexperiencia', 'recorrente' => false, 'pessoa' => false],
    ];

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
        foreach ($this->eventos as $tipo => $config) {
            $data = $this->resolverData($colaborador, $config);
            $this->sincronizarEvento($colaborador, $tipo, $data, $config['recorrente']);
        }
    }

    /**
     * Resolve a data do evento a partir do colaborador ou da pessoa vinculada
     */
    private function resolverData(Colaborador $colaborador, array $config): ?Carbon
    {
        if ($config['pessoa']) {
            return $colaborador->Pessoa?->{$config['campo']};
        }

        return $colaborador->{$config['campo']};
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
