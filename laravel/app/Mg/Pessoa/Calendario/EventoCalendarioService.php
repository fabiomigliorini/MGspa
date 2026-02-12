<?php

namespace Mg\Pessoa\Calendario;

use Exception;
use Google\Service\Exception as GoogleException;

class EventoCalendarioService
{
    // Tipos de Evento
    const TIPO_ANV_COL = 'ANV_COL'; // Aniversário colaborador
    const TIPO_ANV_EMP = 'ANV_EMP'; // Aniversário de empresa colaborador
    const TIPO_EXP_FIM = 'EXP_FIM'; // Vencimento contrato experiência
    const TIPO_EXP_REN = 'EXP_REN'; // Vencimento renovação experiência
    const TIPO_FER_INI = 'FER_INI'; // Início férias
    const TIPO_FER_FIM = 'FER_FIM'; // Fim férias
    const TIPO_ANV_DEP = 'ANV_DEP'; // Aniversário dependente

    const TIPO_LABELS = [
        self::TIPO_ANV_COL => 'Aniversário colaborador',
        self::TIPO_ANV_EMP => 'Aniversário de empresa colaborador',
        self::TIPO_EXP_FIM => 'Vencimento contrato experiência',
        self::TIPO_EXP_REN => 'Vencimento renovação experiência',
        self::TIPO_FER_INI => 'Início férias',
        self::TIPO_FER_FIM => 'Fim férias',
        self::TIPO_ANV_DEP => 'Aniversário dependente',
    ];

    const TIPO_LABELS_CALENDARIO = [
        self::TIPO_ANV_COL => 'Aniversário {colaborador}',
        self::TIPO_ANV_EMP => 'Aniversário Empresa {colaborador}',
        self::TIPO_EXP_FIM => 'Renovação {colaborador}',
        self::TIPO_EXP_REN => 'Efetivação {colaborador}',
        self::TIPO_FER_INI => 'Férias {colaborador}',
        self::TIPO_FER_FIM => 'Volta {colaborador}',
        self::TIPO_ANV_DEP => 'Aniversário {dependente} dependente de {colaborador}',
    ];

    // Status de sincronização com Google Calendar
    const STATUS_PEND = 'PEND'; // Pendente de sincronização
    const STATUS_SINC = 'SINC'; // Sincronizado com sucesso
    const STATUS_ERRO = 'ERRO'; // Erro na sincronização

    const STATUS_LABELS = [
        self::STATUS_PEND => 'Pendente de sincronização',
        self::STATUS_SINC => 'Sincronizado com sucesso',
        self::STATUS_ERRO => 'Erro na sincronização',
    ];

    /**
     * Sincroniza um EventoCalendario com o Google Calendar.
     * Cria, atualiza ou deleta o evento conforme o estado do model.
     */
    public static function sync(EventoCalendario $evento): void
    {
        // Recarrega o model do banco para garantir dados atualizados
        $evento = $evento->fresh();

        if (!$evento) {
            return;
        }

        $calendarService = app(GoogleCalendarService::class);

        try {
            // Evento inativo: deletar do Google se existir
            if ($evento->inativo) {
                if ($evento->googleeventid) {
                    try {
                        $calendarService->deleteEvent($evento->googleeventid);
                    } catch (GoogleException $e) {
                        // 404/410: evento já foi excluído no Google, ignorar
                        if (!in_array($e->getCode(), [404, 410])) {
                            throw $e;
                        }
                    }
                    $evento->googleeventid = null;
                }

                $evento->status = self::STATUS_SINC;
                $evento->save();
                return;
            }

            // Montar dados do evento
            $data = self::buildEventData($evento);

            // Criar ou atualizar no Google Calendar
            if (!$evento->googleeventid) {
                $googleEvent = $calendarService->createEvent($data);
                $evento->googleeventid = $googleEvent->getId();
            } else {
                try {
                    $calendarService->updateEvent($evento->googleeventid, $data);
                } catch (GoogleException $e) {
                    // 404/410: evento foi excluído no Google, recriar
                    if (!in_array($e->getCode(), [404, 410])) {
                        throw $e;
                    }
                    $googleEvent = $calendarService->createEvent($data);
                    $evento->googleeventid = $googleEvent->getId();
                }
            }

            $evento->status = self::STATUS_SINC;
            $evento->save();
        } catch (Exception $e) {
            $evento->status = self::STATUS_ERRO;
            $evento->save();

            throw $e;
        }
    }

    /**
     * Monta o array de dados para o GoogleCalendarService.
     * Evento de dia inteiro (all-day).
     */
    protected static function buildEventData(EventoCalendario $evento): array
    {
        $template = self::TIPO_LABELS_CALENDARIO[$evento->tipo] ?? "Evento {$evento->tipo}";
        $nomeDependente = '';
        $pessoaLink = $evento->Colaborador?->Pessoa;

        // Para dependentes, buscar nomes via Dependente->Pessoa e PessoaResponsavel
        if ($evento->coddependente) {
            $dependente = $evento->Dependente;
            $nomeDependente = $dependente?->Pessoa?->pessoa ?? '';
            $pessoaLink = $dependente?->PessoaResponsavel ?? $pessoaLink;
        }

        $nomeColaborador = $pessoaLink?->pessoa ?? '';

        $summary = str_replace(
            ['{colaborador}', '{dependente}'],
            [$nomeColaborador, $nomeDependente],
            $template
        );

        $link = $pessoaLink ? env('PESSOAS_URL') . "/pessoa/{$pessoaLink->codpessoa}" : '';
        $description = trim(($evento->observacoes ?? '') . "\n" . $link);

        $date = $evento->dataevento->toDateString();
        $endDate = $evento->dataevento->copy()->addDay()->toDateString();

        $data = [
            'summary' => $summary,
            'description' => $description,
            'start' => $date,
            'end' => $endDate,
            'timezone' => 'America/Cuiaba',
            'all_day' => true,
        ];

        if ($evento->recorrente) {
            $data['recurrence'] = ['RRULE:FREQ=YEARLY'];
        }

        return $data;
    }

    /**
     * Resolve a Pessoa vinculada ao evento (via Colaborador ou Dependente)
     */
    protected static function resolverPessoa(EventoCalendario $evento)
    {
        if ($evento->coddependente) {
            return $evento->Dependente?->Pessoa;
        }

        if ($evento->codcolaborador) {
            return $evento->Colaborador?->Pessoa;
        }

        return null;
    }
}
