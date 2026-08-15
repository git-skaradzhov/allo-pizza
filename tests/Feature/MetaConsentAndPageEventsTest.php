<?php

namespace Tests\Feature;

use App\Services\Meta\MetaConsentService;
use App\Services\Meta\MetaEventTokenService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStoreData;
use Tests\Concerns\InteractsWithMetaTracking;
use Tests\TestCase;

class MetaConsentAndPageEventsTest extends TestCase
{
    use CreatesStoreData;
    use InteractsWithMetaTracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->createOpenStore();
        $this->withPixelId();
        $this->enableMetaCapi();
        $this->fakeMetaCapiSuccessful();
        Mail::fake();
    }

    public function test_home_page_does_not_load_meta_pixel_without_consent(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="meta-page-events"', false);
        $response->assertDontSee('https://connect.facebook.net/en_US/fbevents.js', false);
        $response->assertDontSee("fbq('init'", false);
        Http::assertNothingSent();
    }

    public function test_denied_consent_does_not_send_capi_for_page_token(): void
    {
        $page = $this->get('/');
        $events = $this->jsonFromScript($page->getContent(), 'meta-page-events');
        $pageView = $this->firstPageEvent($events, 'PageView');

        $this->assertNotNull($pageView);

        $this->denyMarketingConsent()
            ->postJson(route('meta.events.store'), ['token' => $pageView['token']])
            ->assertOk()
            ->assertJson(['sent' => false]);

        Http::assertNothingSent();
    }

    public function test_consent_endpoint_sets_encrypted_marketing_cookie(): void
    {
        $this->postJson(route('cookie-consent.store'), [
            'analytics' => true,
            'marketing' => true,
        ])
            ->assertOk()
            ->assertCookie(app(MetaConsentService::class)->cookieName(), MetaConsentService::GRANTED);

        $this->postJson(route('cookie-consent.store'), [
            'analytics' => false,
            'marketing' => false,
        ])
            ->assertOk()
            ->assertCookie(app(MetaConsentService::class)->cookieName(), MetaConsentService::DENIED);
    }

    public function test_tracking_endpoint_rejects_purchase_tokens(): void
    {
        $token = app(MetaEventTokenService::class)->encode(
            'Purchase',
            'purchase_order_1',
            'https://example.test/checkout',
            ['value' => 10],
        );

        $this->grantMarketingConsent()
            ->postJson(route('meta.events.store'), ['token' => $token])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_tracking_endpoint_rejects_expired_token(): void
    {
        $token = app(MetaEventTokenService::class)->encode(
            'PageView',
            'evt-expired',
            'https://example.test/',
        );

        $this->travel(31)->minutes();

        $this->grantMarketingConsent()
            ->postJson(route('meta.events.store'), ['token' => $token])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_tracking_endpoint_is_rate_limited(): void
    {
        $this->grantMarketingConsent();

        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('meta.events.store'), ['token' => 'invalid'])->assertStatus(422);
        }

        $this->postJson(route('meta.events.store'), ['token' => 'invalid'])->assertStatus(429);
    }

    public function test_pageview_is_not_emitted_for_api_or_health_endpoints(): void
    {
        $this->getJson('/api/products')->assertOk()->assertDontSee('meta-page-events', false);
        $this->get('/up')->assertOk()->assertDontSee('meta-page-events', false);
        $this->postJson(route('meta.events.store'), ['token' => 'x'])->assertDontSee('meta-page-events', false);
    }

    public function test_view_content_uses_parent_product_id_for_all_sizes(): void
    {
        $product = $this->createProductWithVariant(10);
        $page = $this->get(route('product.show', $product->slug));
        $events = $this->jsonFromScript($page->getContent(), 'meta-page-events');
        $viewContent = $this->firstPageEvent($events, 'ViewContent');

        $this->assertNotNull($viewContent);
        $this->assertSame(['product_'.$product->id], $viewContent['payload']['content_ids']);
        $this->assertSame('product', $viewContent['payload']['content_type']);
        $this->assertSame($product->name, $viewContent['payload']['content_name']);
        $this->assertSame('EUR', $viewContent['payload']['currency']);

        $this->grantMarketingConsent()
            ->postJson(route('meta.events.store'), ['token' => $viewContent['token']])
            ->assertOk();

        $sent = $this->sentCapiEventsNamed('ViewContent');
        $this->assertCount(1, $sent);
        $this->assertSame($viewContent['event_id'], $sent[0]['data'][0]['event_id']);
        $this->assertSame(['product_'.$product->id], $sent[0]['data'][0]['custom_data']['content_ids']);
    }

    public function test_pageview_token_sends_matching_capi_event_after_consent(): void
    {
        $page = $this->get('/');
        $events = $this->jsonFromScript($page->getContent(), 'meta-page-events');
        $pageView = $this->firstPageEvent($events, 'PageView');

        $this->grantMarketingConsent()
            ->postJson(route('meta.events.store'), ['token' => $pageView['token']])
            ->assertOk()
            ->assertJsonPath('sent', 1);

        $sent = $this->sentCapiEventsNamed('PageView');
        $this->assertCount(1, $sent);
        $this->assertSame($pageView['event_id'], $sent[0]['data'][0]['event_id']);
        $this->assertSame('website', $sent[0]['data'][0]['action_source']);
    }

    public function test_javascript_payloads_do_not_contain_email_phone_or_access_token(): void
    {
        $product = $this->createProductWithVariant(10);
        $html = $this->get(route('product.show', $product->slug))->getContent();

        $this->assertStringNotContainsString($this->metaCapiToken, $html);
        $this->assertStringNotContainsString('customer_email', $html);
        $this->assertStringNotContainsString('"em":', $html);
        $this->assertStringNotContainsString('"ph":', $html);
    }
}
