<?php

namespace App\Services;

use App\Models\Tenant\Webhook;
use App\Models\Tenant\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcherService
{
    /**
     * Ten failures in a row and the webhook is switched off.
     *
     * A dead endpoint is the normal case here: somebody sets one up, the
     * service behind it goes away, and every event afterwards spends ten
     * seconds timing out. Retrying forever is worse than stopping.
     */
    private const FAILURES_BEFORE_DISABLING = 10;

    /**
     * Send one event to every webhook subscribed to it.
     */
    public function dispatch(string $event, array $payload): void
    {
        foreach (Webhook::active()->forEvent($event)->get() as $webhook) {
            $this->deliver($webhook, $event, $payload);
        }
    }

    /**
     * Deliver to one webhook and record what came back.
     *
     * Every attempt is written to webhook_logs, successful or not — without
     * it there is no way to answer "did it arrive?" after the fact, which is
     * the only question anybody asks about a webhook.
     */
    public function deliver(Webhook $webhook, string $event, array $payload): WebhookLog
    {
        $body = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $signature = hash_hmac('sha256', (string) json_encode($body), (string) $webhook->secret);

        try {
            $response = Http::withHeaders([
                'X-Webhook-Event' => $event,
                'X-Webhook-Signature' => 'sha256=' . $signature,
            ])->timeout(10)->post($webhook->url, $body);

            $log = $this->record($webhook, $event, $response->status(), $response->successful(), $response->body());

            if ($response->successful()) {
                $webhook->update(['last_triggered_at' => now(), 'failure_count' => 0]);
            } else {
                $this->countFailure($webhook, "HTTP {$response->status()}");
            }

            return $log;
        } catch (\Throwable $e) {
            $this->countFailure($webhook, $e->getMessage());

            return $this->record($webhook, $event, null, false, $e->getMessage());
        }
    }

    private function record(Webhook $webhook, string $event, ?int $status, bool $success, ?string $body): WebhookLog
    {
        return WebhookLog::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'response_status' => $status,
            'success' => $success,
            // Enough of the response to recognise the problem, not the whole
            // of somebody's HTML error page.
            'response_body' => $body === null ? null : mb_substr($body, 0, 2000),
        ]);
    }

    private function countFailure(Webhook $webhook, string $reason): void
    {
        $webhook->increment('failure_count');
        Log::warning("Webhook {$webhook->id} failed: {$reason}");

        if ($webhook->fresh()->failure_count >= self::FAILURES_BEFORE_DISABLING) {
            $webhook->update(['is_active' => false]);
            Log::warning("Webhook {$webhook->id} switched off after " . self::FAILURES_BEFORE_DISABLING . ' failures.');
        }
    }
}
