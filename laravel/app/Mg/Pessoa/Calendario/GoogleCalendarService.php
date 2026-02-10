<?php

namespace Mg\Pessoa\Calendario;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
    protected Calendar $calendarService;
    protected string $calendarId;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(base_path(env('GOOGLE_CREDENTIALS_PATH')));
        $client->addScope(Calendar::CALENDAR);

        $this->calendarService = new Calendar($client);
        $this->calendarId = env('GOOGLE_CALENDAR_ID');

        if (!$this->calendarId) {
            throw new \RuntimeException('GOOGLE_CALENDAR_ID não configurado.');
        }
    }

    /**
     * Cria um evento no Google Calendar
     */
    public function createEvent(array $data): Event
    {
        $event = $this->buildEvent($data);

        return $this->calendarService->events->insert($this->calendarId, $event);
    }

    /**
     * Atualiza um evento existente no Google Calendar
     */
    public function updateEvent(string $eventId, array $data): Event
    {
        $event = $this->calendarService->events->get($this->calendarId, $eventId);

        $updated = $this->buildEvent($data);

        $event->setSummary($updated->getSummary());
        $event->setDescription($updated->getDescription());
        $event->setStart($updated->getStart());
        $event->setEnd($updated->getEnd());
        $event->setRecurrence($updated->getRecurrence() ?? []);

        return $this->calendarService->events->update($this->calendarId, $eventId, $event);
    }


    /**
     * Remove um evento do Google Calendar
     */
    public function deleteEvent(string $eventId): void
    {
        $this->calendarService->events->delete($this->calendarId, $eventId);
    }

    /**
     * Monta o objeto Event a partir do array de dados
     */
    protected function buildEvent(array $data): Event
    {

        foreach (['summary', 'start', 'end'] as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório: {$field}");
            }
        }

        $event = new Event([
            'summary' => $data['summary'],
            'description' => $data['description'] ?? null,
        ]);

        $timezone = $data['timezone'] ?? 'America/Cuiaba';

        $start = new EventDateTime();
        $end = new EventDateTime();

        if (!empty($data['all_day'])) {
            // Evento de dia inteiro
            $start->setDate($data['start']);
            $end->setDate($data['end']);
        } else {
            // Evento com horário
            $start->setDateTime($data['start']);
            $start->setTimeZone($timezone);

            $end->setDateTime($data['end']);
            $end->setTimeZone($timezone);
        }

        $event->setStart($start);
        $event->setEnd($end);

        if (!empty($data['recurrence'])) {
            $event->setRecurrence($data['recurrence']);
        }

        return $event;
    }
}
