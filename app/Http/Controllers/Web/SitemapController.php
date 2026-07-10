<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\NewMenuHighlight;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            $this->entry(route('home'), now(), 'daily', config('seo.sitemap.home_priority')),
            $this->entry(route('menu'), now(), 'weekly', config('seo.sitemap.menu_priority')),
            $this->entry(route('lunch.index'), now(), 'weekly', config('seo.sitemap.lunch_priority')),
        ]);

        if (NewMenuHighlight::isPublished()) {
            $urls->push($this->entry(route('new-menu.index'), now(), 'weekly', config('seo.sitemap.menu_priority')));
        }

        Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->each(fn (Category $category) => $urls->push(
                $this->entry(
                    route('category.show', $category->slug),
                    $category->updated_at,
                    'weekly',
                    config('seo.sitemap.category_priority'),
                )
            ));

        Product::query()
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'name', 'updated_at', 'image'])
            ->each(function (Product $product) use ($urls): void {
                $entry = $this->entry(
                    route('product.show', $product->slug),
                    $product->updated_at,
                    'weekly',
                    config('seo.sitemap.product_priority'),
                );

                if ($imageUrl = product_image_url($product->image, product_image_tier_size('large'))) {
                    $entry['images'] = [[
                        'loc' => absolute_url($imageUrl),
                        'title' => $product->name ?? $product->slug,
                    ]];
                }

                $urls->push($entry);
            });

        Page::query()
            ->where('is_active', true)
            ->whereNotIn('slug', ['home-info', 'obedno-menyu', 'novo-v-menuto'])
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'updated_at'])
            ->each(fn (Page $page) => $urls->push(
                $this->entry(
                    route('pages.show', $page->slug),
                    $page->updated_at,
                    'monthly',
                    config('seo.sitemap.page_priority'),
                )
            ));

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function entry(string $loc, $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
