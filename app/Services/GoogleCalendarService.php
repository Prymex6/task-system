<?php

namespace App\Services;

use App\Models\Tenant\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private static function token(): ?string
    {
        return IntegrationService::get('google_calendar')?->config['access_token'] ?? null;
    }

    private static function calendarId(): string
    {
        return IntegrationService::get('google_calendar')?->config['calendar_id'] ?? 'primary';
    }

    public static function syncTask(Task $task): bool
    {
        $token = static::token();
        if (!$token || !$task->due_date) {
            return false;
        }

        try {
            $event = [
                'summary' => $task->title,
                'description' => $task->description,
                'start' => ['date' => $task->due_date->toDateString()],
                'end' => ['date' => $task->due_date->toDateString()],
            ];

            $calId = urlencode(static::calendarId());
            $response = Http::withToken($token)
                ->post("https://www.googleapis.com/calendar/v3/calendars/{$calId}/events", $event);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('GoogleCalendarService: failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public static function getEvents(string $from, string $to): array
    {
        $token = static::token();
        if (!$token) {
            return [];
        }

        try {
            $calId = urlencode(static::calendarId());
            $response = Http::withToken($token)->get(
                "https://www.googleapis.com/calendar/v3/calendars/{$calId}/events",
                ['timeMin' => $from . 'T00:00:00Z', 'timeMax' => $to . 'T23:59:59Z']
            );

            return $response->json('items') ?? [];
        } catch (\Throwable $e) {
            Log::error('GoogleCalendarService: getEvents failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
