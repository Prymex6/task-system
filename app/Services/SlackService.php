<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public static function send(string $message, ?string $webhookUrl = null): bool
    {
        $url = $webhookUrl ?? IntegrationService::get('slack')?->config['webhook_url'] ?? null;

        if (!$url) {
            return false;
        }

        try {
            $response = Http::post($url, ['text' => $message]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SlackService: send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public static function taskAssigned(string $taskTitle, string $assignee, string $url): void
    {
        static::send("*Nowe zadanie:* <{$url}|{$taskTitle}> przypisano do _{$assignee}_");
    }

    public static function invoicePaid(string $invoiceNumber, float $amount, string $currency): void
    {
        static::send("*Faktura zapłacona:* {$invoiceNumber} — " . number_format($amount, 2) . " {$currency} ✅");
    }

    public static function ticketCreated(string $title, string $priority): void
    {
        $emoji = ['urgent' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'][$priority] ?? '⚪';
        static::send("{$emoji} *Nowy ticket:* {$title} [{$priority}]");
    }
}
