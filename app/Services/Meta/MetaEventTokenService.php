<?php

namespace App\Services\Meta;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MetaEventTokenService
{
    /**
     * @param  array<string, mixed>  $customData
     */
    public function encode(string $eventName, string $eventId, string $eventSourceUrl, array $customData = []): string
    {
        return Crypt::encryptString(json_encode([
            'event_name' => $eventName,
            'event_id' => $eventId,
            'event_source_url' => $eventSourceUrl,
            'custom_data' => $customData,
            'created_at' => now()->getTimestamp(),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{
     *     event_name: string,
     *     event_id: string,
     *     event_source_url: string,
     *     custom_data: array<string, mixed>,
     *     created_at: int
     * }|null
     */
    public function decode(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($payload) || ! $this->isValidPayload($payload)) {
            return null;
        }

        $ttl = (int) config('meta.event_token_ttl', 1800);

        if (now()->getTimestamp() - (int) $payload['created_at'] > $ttl) {
            return null;
        }

        return [
            'event_name' => (string) $payload['event_name'],
            'event_id' => (string) $payload['event_id'],
            'event_source_url' => (string) $payload['event_source_url'],
            'custom_data' => is_array($payload['custom_data'] ?? null) ? $payload['custom_data'] : [],
            'created_at' => (int) $payload['created_at'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isValidPayload(array $payload): bool
    {
        foreach (['event_name', 'event_id', 'event_source_url', 'created_at'] as $key) {
            if (! array_key_exists($key, $payload)) {
                return false;
            }
        }

        return is_string($payload['event_name'])
            && is_string($payload['event_id'])
            && is_string($payload['event_source_url'])
            && is_numeric($payload['created_at']);
    }

    public function logInvalidToken(): void
    {
        Log::info('Meta event token rejected.');
    }
}
