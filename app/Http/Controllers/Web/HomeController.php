<?php

namespace App\Http\Controllers\Web;

use App\Enums\BannerPosition;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\LunchMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\StoreService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
    ) {}

    public function index(): View
    {
        $storeSetting = $this->storeService->settings();

        $menuCategories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->with(['products' => fn ($query) => $query->where('is_active', true)->with('variants')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('pages.home', [
            'heroBanners' => Banner::query()->active()->where('position', BannerPosition::HomeHero)->orderBy('sort_order')->get(),
            'smallBanners' => Banner::query()->active()->where('position', BannerPosition::HomeSmallCards)->orderBy('sort_order')->get(),
            'promoBanners' => Banner::query()->active()->where('position', BannerPosition::PromoSection)->orderBy('sort_order')->get(),
            'featuredProducts' => Product::query()->where('is_active', true)->where('is_featured', true)->with('variants')->inRandomOrder()->limit(6)->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'menuCategories' => $menuCategories,
            'lunchMenu' => LunchMenu::query()
                ->where('is_active', true)
                ->with(['items' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('sort_order')
                ->first(),
            'homeInfoPage' => Page::query()->where('slug', 'home-info')->where('is_active', true)->first(),
            'storeSetting' => $storeSetting,
            'isOpen' => $this->storeService->isOpen(),
            'workingHoursMessage' => $this->storeService->workingHoursMessage(),
        ]);
    }
}
