<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaConversionsApiService
{
    public function __construct(
        protected MetaPixelIdResolver $pixelIdResolver,
        protected MetaConsentService $consentService,
    ) {}

    public function isConfigured(): bool
    {
        return (bool) config('meta.capi.enabled')
            && $this->accessToken() !== null
            && $this->pixelIdResolver->resolve() !== null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function send(array $event, bool $enforceConsent = true): bool
    {
        if ($enforceConsent && ! $this->consentService->hasGranted()) {
            return false;
        }

        if (! $this->isConfigured()) {
            return false;
        }

        $payload = [
            'data' => [$event],
        ];

        $testEventCode = $this->testEventCode();

        if ($testEventCode !== null) {
            $payload['test_event_code'] = $testEventCode;
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/events',
            trim((string) config('meta.capi.api_version', 'v26.0'), '/'),
            $this->pixelIdResolver->resolve(),
        );

        try {
            $send = fn () => Http::timeout((int) config('meta.capi.timeout', 3))
                ->asJson()
                ->post($url, $this->payloadWithToken($payload));

            $response = $this->sendWithRetry($send);
        } catch (Throwable $exception) {
            Log::warning('Meta CAPI event exception.', $this->logContext(
                $event,
                null,
                $this->sanitize($exception->getMessage()),
            ));

            return false;
        }

        if ($response->successful()) {
            Log::info('Meta CAPI event sent.', $this->logContext($event, $response->status()));

            return true;
        }

        Log::warning('Meta CAPI event failed.', $this->logContext(
            $event,
            $response->status(),
            $this->sanitize($response->body()),
        ));

        return false;
    }

    /**
     * @param  callable(): Response  $send
     */
    protected function sendWithRetry(callable $send): Response
    {
        try {
            $response = $send();
        } catch (ConnectionException) {
            $response = $send();
        }

        if ($response->serverError()) {
            try {
                $response = $send();
            } catch (ConnectionException) {
                return $response;
            }
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function payloadWithToken(array $payload): array
    {
        $payload['access_token'] = $this->accessToken();

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    protected function logContext(array $event, ?int $status, ?string $error = null): array
    {
        $context = [
            'event_name' => $event['event_name'] ?? null,
            'event_id' => $event['event_id'] ?? null,
            'status' => $status,
        ];

        if ($error !== null && $error !== '') {
            $context['error'] = $error;
        }

        return $context;
    }

    public function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $token = $this->accessToken();

        if ($token !== null) {
            $value = str_replace($token, '[redacted]', $value);
        }

        return mb_substr($value, 0, 500);
    }

    public function testEventCode(): ?string
    {
        $code = trim((string) config('meta.capi.test_event_code', ''));

        return $code === '' ? null : $code;
    }

    protected function accessToken(): ?string
    {
        $token = trim((string) config('meta.capi.access_token', ''));

        return $token === '' ? null : $token;
    }
}
