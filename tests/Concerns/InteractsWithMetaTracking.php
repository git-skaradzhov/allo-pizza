<?php

namespace Tests\Concerns;

use App\Models\StoreSetting;
use App\Services\Meta\MetaConsentService;
use Illuminate\Support\Facades\Http;

trait InteractsWithMetaTracking
{
    protected string $metaCapiToken = 'test-capi-access-token-secret';

    protected function enableMetaCapi(array $overrides = []): void
    {
        config(array_merge([
            'meta.capi.enabled' => true,
            'meta.capi.access_token' => $this->metaCapiToken,
            'meta.capi.api_version' => 'v26.0',
            'meta.capi.test_event_code' => '',
            'meta.capi.timeout' => 3,
            'meta.capi.max_purchase_attempts' => 10,
        ], $overrides));
    }

    protected function withPixelId(string $pixelId = '1385265280465389'): StoreSetting
    {
        $settings = StoreSetting::current();
        $settings->update(['meta_pixel_id' => $pixelId]);

        return $settings->fresh();
    }

    protected function grantMarketingConsent(): static
    {
        return $this->withCredentials()->withCookie(
            app(MetaConsentService::class)->cookieName(),
            MetaConsentService::GRANTED,
        );
    }

    protected function denyMarketingConsent(): static
    {
        return $this->withCredentials()->withCookie(
            app(MetaConsentService::class)->cookieName(),
            MetaConsentService::DENIED,
        );
    }

    protected function fakeMetaCapiSuccessful(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
    }

    protected function jsonFromScript(string $html, string $elementId): mixed
    {
        if (! preg_match('/id="'.preg_quote($elementId, '/').'"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            return null;
        }

        return json_decode(html_entity_decode(trim($matches[1])), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function sentCapiPayloads(): array
    {
        return Http::recorded()
            ->map(fn (array $pair) => $pair[0]->data())
            ->filter(fn ($data) => is_array($data) && isset($data['data'][0]))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function sentCapiEventsNamed(string $eventName): array
    {
        return collect($this->sentCapiPayloads())
            ->filter(fn (array $payload) => ($payload['data'][0]['event_name'] ?? null) === $eventName)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     */
    protected function firstPageEvent(array $events, string $eventName): ?array
    {
        foreach ($events as $event) {
            if (($event['event_name'] ?? null) === $eventName) {
                return $event;
            }
        }

        return null;
    }
}
