<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewMenuHighlight;
use App\Models\Page;
use Illuminate\View\View;

class NewMenuHighlightController extends Controller
{
    public function index(): View
    {
        $page = Page::query()
            ->where('slug', 'novo-v-menuto')
            ->where('is_active', true)
            ->first();

        $highlight = NewMenuHighlight::query()
            ->where('new_menu_highlights.is_active', true)
            ->whereHas('products', fn ($query) => $query->where('products.is_active', true))
            ->with([
                'products' => fn ($query) => $query
                    ->where('products.is_active', true)
                    ->with(['variants', 'category'])
                    ->orderByPivot('sort_order'),
            ])
            ->orderBy('new_menu_highlights.sort_order')
            ->first();

        return view('pages.new-menu', compact('page', 'highlight'));
    }
}
