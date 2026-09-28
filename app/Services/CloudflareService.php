<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private static function apiToken(): ?string
    {
        return config('services.cloudflare.api_token');
    }

    private static function zoneId(): ?string
    {
        return config('services.cloudflare.zone_id');
    }

    public static function isConfigured(): bool
    {
        return !empty(static::apiToken()) && !empty(static::zoneId());
    }

    public static function addSubdomain(string $subdomain, string $target): array
    {
        if (!static::isConfigured()) {
            return ['success' => false, 'message' => 'Cloudflare nie skonfigurowany.'];
        }

        try {
            $response = Http::withToken(static::apiToken())
                ->post('https://api.cloudflare.com/client/v4/zones/' . static::zoneId() . '/dns_records', [
                    'type' => 'CNAME',
                    'name' => $subdomain,
                    'content' => $target,
                    'ttl' => 1,
                    'proxied' => true,
                ]);

            $data = $response->json();

            if ($data['success'] ?? false) {
                return ['success' => true, 'record_id' => $data['result']['id']];
            }

            $error = $data['errors'][0]['message'] ?? 'Unknown error';
            Log::warning("Cloudflare addSubdomain failed: {$error}", ['subdomain' => $subdomain]);

            return ['success' => false, 'message' => $error];
        } catch (\Throwable $e) {
            Log::error("Cloudflare addSubdomain exception: {$e->getMessage()}");

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function removeSubdomain(string $subdomain): array
    {
        if (!static::isConfigured()) {
            return ['success' => false, 'message' => 'Cloudflare nie skonfigurowany.'];
        }

        try {
            // Find the DNS record first
            $listResponse = Http::withToken(static::apiToken())
                ->get('https://api.cloudflare.com/client/v4/zones/' . static::zoneId() . '/dns_records', [
                    'name' => $subdomain,
                    'type' => 'CNAME',
                ]);

            $records = $listResponse->json('result', []);
            if (empty($records)) {
                return ['success' => true, 'message' => 'Rekord nie istnieje.'];
            }

            $recordId = $records[0]['id'];
            $deleteResponse = Http::withToken(static::apiToken())
                ->delete('https://api.cloudflare.com/client/v4/zones/' . static::zoneId() . "/dns_records/{$recordId}");

            $data = $deleteResponse->json();

            if ($data['success'] ?? false) {
                return ['success' => true];
            }

            $error = $data['errors'][0]['message'] ?? 'Unknown error';

            return ['success' => false, 'message' => $error];
        } catch (\Throwable $e) {
            Log::error("Cloudflare removeSubdomain exception: {$e->getMessage()}");

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
