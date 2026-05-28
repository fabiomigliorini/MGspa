<?php

namespace Mg\Pessoa\Calendario;

use Exception;
use Google\Service\Exception as GoogleException;
use Illuminate\Support\Facades\Log;

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
        self::TIPO_ANV_COL => '{colaborador} - Aniversário',
        self::TIPO_ANV_EMP => '{colaborador} - Aniversário Empresa',
        self::TIPO_EXP_FIM => '{colaborador} - Renovação',
        self::TIPO_EXP_REN => '{colaborador} - Efetivação',
        self::TIPO_FER_INI => '{colaborador} - Início das Férias',
        self::TIPO_FER_FIM => '{colaborador} - Volta das Férias',
        self::TIPO_ANV_DEP => '{dependente} - Anivesário de dependente do Colaborador {colaborador}',
    ];

    // Google Calendar ID por tipo de evento
    const TIPO_CALENDARIOS = [
        self::TIPO_ANV_COL => 'GOOGLE_CALENDAR_ID_ANIVERSARIOS',
        self::TIPO_ANV_EMP => 'GOOGLE_CALENDAR_ID_ANIVERSARIOS',
        self::TIPO_ANV_DEP => 'GOOGLE_CALENDAR_ID_ANIVERSARIOS',
        self::TIPO_EXP_FIM => 'GOOGLE_CALENDAR_ID_EVENTOS_RH',
        self::TIPO_EXP_REN => 'GOOGLE_CALENDAR_ID_EVENTOS_RH',
        self::TIPO_FER_INI => 'GOOGLE_CALENDAR_ID_EVENTOS_RH',
        self::TIPO_FER_FIM => 'GOOGLE_CALENDAR_ID_EVENTOS_RH',
    ];

    // Google Calendar colorId por tipo de evento
    const TIPO_CORES = [
        self::TIPO_ANV_COL => '5',  // Banana (Amarelo)
        self::TIPO_ANV_EMP => '6',  // Tangerine (Laranja)
        self::TIPO_EXP_FIM => '3',  // Grape (Roxo)
        self::TIPO_EXP_REN => '3',  // Grape (Roxo)
        self::TIPO_FER_INI => '11', // Tomato (Vermelho)
        self::TIPO_FER_FIM => '10', // Basil (Verde)
        self::TIPO_ANV_DEP => '4',  // Flamingo (Rosa)
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
     * Resolve o Google Calendar ID para o tipo de evento
     */
    public static function resolverCalendarId(string $tipo): string
    {
        $envKey = self::TIPO_CALENDARIOS[$tipo] ?? null;

        if (!$envKey || !env($envKey)) {
            throw new \RuntimeException("Calendar ID não configurado para tipo: {$tipo} (env: {$envKey})");
        }

        return env($envKey);
    }

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
        $calendarId = self::resolverCalendarId($evento->tipo);

        try {
            // Evento inativo: deletar do Google se existir
            if ($evento->inativo) {
                if ($evento->googleeventid) {
                    try {
                        $calendarService->deleteEvent($calendarId, $evento->googleeventid);
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
                Log::info('EventoCalendario::sync - criando evento', [
                    'codeventocalendario' => $evento->codeventocalendario,
                    'tipo' => $evento->tipo,
                ]);
                $googleEvent = $calendarService->createEvent($calendarId, $data);
                $evento->googleeventid = $googleEvent->getId();
            } else {
                try {
                    Log::info('EventoCalendario::sync - atualizando evento', [
                        'codeventocalendario' => $evento->codeventocalendario,
                        'googleeventid' => $evento->googleeventid,
                    ]);
                    $calendarService->updateEvent($calendarId, $evento->googleeventid, $data);
                } catch (GoogleException $e) {
                    Log::warning('EventoCalendario::sync - erro no update, tentando recriar', [
                        'codeventocalendario' => $evento->codeventocalendario,
                        'googleeventid' => $evento->googleeventid,
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]);
                    // 404/410: evento foi excluído no Google, recriar
                    if (!in_array($e->getCode(), [404, 410])) {
                        throw $e;
                    }
                    $googleEvent = $calendarService->createEvent($calendarId, $data);
                    $evento->googleeventid = $googleEvent->getId();
                    Log::info('EventoCalendario::sync - evento recriado', [
                        'codeventocalendario' => $evento->codeventocalendario,
                        'googleeventid' => $evento->googleeventid,
                    ]);
                }
            }

            $evento->status = self::STATUS_SINC;
            $evento->save();
            Log::info('EventoCalendario::sync - sincronizado com sucesso', [
                'codeventocalendario' => $evento->codeventocalendario,
                'googleeventid' => $evento->googleeventid,
            ]);
        } catch (Exception $e) {
            Log::error('EventoCalendario::sync - erro na sincronização', [
                'codeventocalendario' => $evento->codeventocalendario,
                'googleeventid' => $evento->googleeventid,
                'exception' => $e->getMessage(),
            ]);
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
            'colorId' => self::TIPO_CORES[$evento->tipo] ?? null,
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
