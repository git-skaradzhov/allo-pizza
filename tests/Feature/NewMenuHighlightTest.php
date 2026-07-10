<?php

namespace Tests\Feature;

use App\Models\NewMenuHighlight;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class NewMenuHighlightTest extends TestCase
{
    use CreatesStoreData;
    use RefreshDatabase;

    public function test_home_page_shows_new_menu_section_when_active_with_products(): void
    {
        $this->createOpenStore();

        $product = Product::factory()->create([
            'name' => 'Крудо',
            'is_new' => true,
        ]);

        $highlight = NewMenuHighlight::query()->create([
            'title' => 'Ново в менюто',
            'message' => 'Свежи предложения',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $highlight->products()->attach($product->id, ['sort_order' => 1]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ново в менюто')
            ->assertSee('Свежи предложения')
            ->assertSee('Крудо');
    }

    public function test_home_page_hides_new_menu_section_when_inactive(): void
    {
        $this->createOpenStore();

        $product = Product::factory()->create([
            'name' => 'Крудо',
            'is_new' => true,
        ]);

        $highlight = NewMenuHighlight::query()->create([
            'title' => 'Ново в менюто',
            'message' => 'Свежи предложения',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $highlight->products()->attach($product->id, ['sort_order' => 1]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Свежи предложения');
    }
}
