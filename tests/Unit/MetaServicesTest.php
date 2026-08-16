<?php

namespace Tests\Unit;

use App\Services\Meta\MetaConsentService;
use App\Services\Meta\MetaConversionsApiService;
use App\Services\Meta\MetaEventFactory;
use App\Services\Meta\MetaEventTokenService;
use App\Services\Meta\MetaUserDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesStoreData;
use Tests\Concerns\InteractsWithMetaTracking;
use Tests\TestCase;

class MetaServicesTest extends TestCase
{
    use CreatesStoreData;
    use InteractsWithMetaTracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createOpenStore();
        $this->withPixelId();
        $this->enableMetaCapi();
    }

    public function test_event_token_is_encrypted_and_has_ttl(): void
    {
        $service = app(MetaEventTokenService::class);
        $token = $service->encode('PageView', 'event-1', 'https://example.test/', ['value' => 1.5]);

        $this->assertNotSame('PageView', $token);
        $this->assertStringNotContainsString('PageView', $token);

        $decoded = $service->decode($token);

        $this->assertSame('PageView', $decoded['event_name']);
        $this->assertSame('event-1', $decoded['event_id']);
        $this->assertSame(['value' => 1.5], $decoded['custom_data']);
    }

    public function test_expired_event_token_is_rejected(): void
    {
        $service = app(MetaEventTokenService::class);
        $token = $service->encode('ViewContent', 'event-2', 'https://example.test/product');

        $this->travel(31)->minutes();

        $this->assertNull($service->decode($token));
    }

    public function test_invalid_event_token_is_rejected(): void
    {
        $this->assertNull(app(MetaEventTokenService::class)->decode('not-a-valid-token'));
    }

    public function test_email_and_phone_are_sha256_hashed(): void
    {
        $builder = app(MetaUserDataBuilder::class);

        $this->assertSame(
            hash('sha256', 'ivan@example.com'),
            $builder->hashEmail('  Ivan@Example.com  ')
        );
        $this->assertSame(
            hash('sha256', '359888123456'),
            $builder->hashPhone('0888 123 456')
        );

        $request = Request::create('https://example.test/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'MetaTestAgent/1.0',
        ]);
        $request->cookies->set('_fbp', 'fb.1.123');
        $request->cookies->set('_fbc', 'fb.1.456');

        $userData = $builder->fromRequest($request);

        $this->assertSame('203.0.113.10', $userData['client_ip_address']);
        $this->assertSame('MetaTestAgent/1.0', $userData['client_user_agent']);
        $this->assertArrayNotHasKey('em', $userData);
        $this->assertArrayNotHasKey('ph', $userData);
    }

    public function test_test_event_code_is_added_only_when_configured(): void
    {
        $this->fakeMetaCapiSuccessful();

        $event = app(MetaEventFactory::class)->makeEvent(
            'PageView',
            'evt-1',
            'https://example.test/',
        );

        app(MetaConversionsApiService::class)->send($event, enforceConsent: false);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ! array_key_exists('test_event_code', $data)
                && ($data['data'][0]['event_name'] ?? null) === 'PageView';
        });

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
        $this->enableMetaCapi(['meta.capi.test_event_code' => 'TEST41633']);

        app(MetaConversionsApiService::class)->send($event, enforceConsent: false);

        Http::assertSent(function ($request) {
            return ($request->data()['test_event_code'] ?? null) === 'TEST41633';
        });
    }

    public function test_access_token_is_not_written_to_logs(): void
    {
        $service = app(MetaConversionsApiService::class);

        $this->assertStringNotContainsString($this->metaCapiToken, (string) $service->sanitize('Meta error '.$this->metaCapiToken));
        $this->assertStringContainsString('[redacted]', (string) $service->sanitize('Meta error '.$this->metaCapiToken));
    }

    public function test_consent_cookie_name_is_stable(): void
    {
        $this->assertSame('marketing_consent', app(MetaConsentService::class)->cookieName());
    }
}
