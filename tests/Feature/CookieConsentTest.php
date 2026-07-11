<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\StoreSetting;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use CreatesStoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createOpenStore();
    }

    public function test_home_page_includes_cookie_consent_banner_markup(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="cookie-consent-banner"', false);
        $response->assertSee('id="cookie-consent-settings"', false);
        $response->assertSee('Приемам всички', false);
        $response->assertSee('Само необходими', false);
        $response->assertSee('Настройки за бисквитки', false);
    }

    public function test_cookie_policy_page_is_accessible(): void
    {
        Page::query()->create([
            'title' => 'Политика за бисквитки',
            'slug' => 'politika-za-biskvitki',
            'content' => '<p>Тестово съдържание за бисквитки.</p>',
            'is_active' => true,
        ]);

        $response = $this->get('/pages/politika-za-biskvitki');

        $response->assertOk();
        $response->assertSee('Политика за бисквитки', false);
        $response->assertSee('Тестово съдържание за бисквитки.', false);
    }

    public function test_footer_includes_cookie_policy_link(): void
    {
        Page::query()->create([
            'title' => 'Политика за бисквитки',
            'slug' => 'politika-za-biskvitki',
            'content' => '<p>Тест</p>',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('pages.show', 'politika-za-biskvitki'), false);
        $response->assertSee('data-cookie-settings-open', false);
    }

    public function test_tracking_scripts_are_not_rendered_inline_when_configured(): void
    {
        StoreSetting::current()->update([
            'google_analytics_id' => 'G-TEST123456',
            'google_tag_manager_id' => 'GTM-TEST123',
            'meta_pixel_id' => '123456789012345',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="analytics-config"', false);
        $response->assertSee('analytics_storage', false);
        $response->assertSee('"gaId":"G-TEST123456"', false);
        $response->assertSee('"gtmId":"GTM-TEST123"', false);
        $response->assertSee('"metaPixelId":"123456789012345"', false);
        $response->assertDontSee('https://www.googletagmanager.com/gtm.js?id=GTM-TEST123', false);
        $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123456', false);
        $response->assertDontSee('https://connect.facebook.net/en_US/fbevents.js', false);
        $response->assertDontSee('fbq(\'init\'', false);
    }
}
