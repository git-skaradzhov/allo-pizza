<?php

namespace Tests\Feature;

use App\Http\Controllers\Web\PhoneRevealController;
use App\Models\StoreSetting;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesStoreData;
use Tests\Concerns\InteractsWithMetaTracking;
use Tests\TestCase;

class PhoneRevealTest extends TestCase
{
    use CreatesStoreData;
    use InteractsWithMetaTracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->createOpenStore();
        StoreSetting::query()->first()->update([
            'store_phone' => '0899 679 006',
            'store_phone_secondary' => '0899 679 710',
        ]);
        $this->withPixelId();
        $this->enableMetaCapi();
        $this->fakeMetaCapiSuccessful();
    }

    public function test_home_page_hides_phone_numbers_behind_reveal_button(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Покажи телефоните', false);
        $response->assertSee('data-phone-reveal-trigger', false);
        $response->assertSee('data-phone-reveal-numbers', false);
        $response->assertSee('0899 679 006', false);
        $response->assertSee('tel:0899679006', false);
        $this->assertSame(1, substr_count($response->getContent(), 'phoneRevealUrl'));
    }

    public function test_reveal_endpoint_sends_contact_capi_event_with_consent(): void
    {
        $response = $this->grantMarketingConsent()
            ->postJson(route('phone-reveal.store'), [
                'event_source_url' => 'http://localhost/menu',
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('sent', true);

        $eventId = $response->json('event_id');
        $this->assertNotEmpty($eventId);

        $sent = $this->sentCapiEventsNamed('Contact');
        $this->assertCount(1, $sent);
        $this->assertSame($eventId, $sent[0]['data'][0]['event_id']);
        $this->assertSame('phone_reveal', $sent[0]['data'][0]['custom_data']['content_name']);
        $this->assertSame('http://localhost/menu', $sent[0]['data'][0]['event_source_url']);
        $this->assertSame($eventId, session(PhoneRevealController::SESSION_EVENT_ID));
    }

    public function test_reveal_endpoint_does_not_send_capi_without_consent(): void
    {
        $this->postJson(route('phone-reveal.store'))
            ->assertOk()
            ->assertJsonPath('sent', false)
            ->assertJsonPath('ok', true);

        Http::assertNothingSent();
    }

    public function test_repeat_reveal_in_same_session_reuses_event_id_and_does_not_resend(): void
    {
        $first = $this->grantMarketingConsent()
            ->postJson(route('phone-reveal.store'))
            ->assertOk()
            ->assertJsonPath('sent', true);

        $eventId = $first->json('event_id');

        $second = $this->grantMarketingConsent()
            ->postJson(route('phone-reveal.store'))
            ->assertOk()
            ->assertJsonPath('sent', false)
            ->assertJsonPath('event_id', $eventId);

        $this->assertCount(1, $this->sentCapiEventsNamed('Contact'));
    }

    public function test_reveal_endpoint_rejects_foreign_event_source_url(): void
    {
        $this->grantMarketingConsent()
            ->postJson(route('phone-reveal.store'), [
                'event_source_url' => 'https://evil.example/steal',
            ])
            ->assertOk()
            ->assertJsonPath('sent', true);

        $sent = $this->sentCapiEventsNamed('Contact');
        $this->assertCount(1, $sent);
        $this->assertNotSame('https://evil.example/steal', $sent[0]['data'][0]['event_source_url']);
    }
}
