<?php

namespace Tests\Feature;

use App\Enums\BannerPosition;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HighlightedBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('banners/combo.png', $this->png());
        Storage::disk('public')->put('banners/other.png', $this->png());
    }

    public function test_highlighted_image_only_banner_leads_the_row_without_overlay_text(): void
    {
        $this->travelTo('2026-11-18 12:00:00');

        Banner::query()->create([
            'title' => 'Работно време',
            'position' => BannerPosition::HomeSmallCards,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Banner::query()->create([
            'title' => 'Меню',
            'image' => 'banners/other.png',
            'position' => BannerPosition::HomeSmallCards,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Banner::query()->create([
            'title' => 'Комбо меню',
            'subtitle' => 'Скрит подзаглавен текст',
            'image' => 'banners/combo.png',
            'image_only' => true,
            'is_highlighted' => true,
            'button_url' => '/pages/kombo-menyu',
            'position' => BannerPosition::HomeSmallCards,
            'is_active' => true,
            'sort_order' => 5,
            'starts_at' => '2026-11-18 00:00:00',
            'ends_at' => '2026-11-25 23:59:59',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $html = $response->getContent();
        $slider = strpos($html, 'Предишни промо банери');
        $accent = strpos($html, 'promo-accent', $slider);
        $hours = strpos($html, 'Работно време', $slider);
        $other = strpos($html, '/storage/banners/other.png', $slider);

        $this->assertNotFalse($slider);
        $this->assertNotFalse($accent);
        $this->assertNotFalse($hours);
        $this->assertNotFalse($other);
        $this->assertLessThan($hours, $accent);
        $this->assertLessThan($other, $hours);
        $response->assertSee('/pages/kombo-menyu', false);
        $response->assertSee('object-contain', false);
        $response->assertDontSee('Скрит подзаглавен текст', false);
    }

    public function test_highlighted_banner_is_hidden_outside_its_schedule(): void
    {
        Banner::query()->create([
            'title' => 'Комбо меню',
            'image' => 'banners/combo.png',
            'image_only' => true,
            'is_highlighted' => true,
            'button_url' => '/pages/kombo-menyu',
            'position' => BannerPosition::HomeSmallCards,
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => '2026-11-18 00:00:00',
            'ends_at' => '2026-11-25 23:59:59',
        ]);

        $this->travelTo('2026-11-17 23:59:00');
        $this->get('/')->assertDontSee('promo-accent', false);

        $this->travelTo('2026-11-18 00:00:00');
        $this->get('/')->assertSee('promo-accent', false);

        $this->travelTo('2026-11-25 23:59:59');
        $this->get('/')->assertSee('promo-accent', false);

        $this->travelTo('2026-11-26 00:00:00');
        $this->get('/')->assertDontSee('promo-accent', false);
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(40, 40);
        $color = imagecolorallocate($image, 220, 50, 50);
        imagefilledrectangle($image, 0, 0, 40, 40, $color);
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
};
