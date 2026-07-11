<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\PromoService;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected DeliveryService $deliveryService,
        protected StoreService $storeService,
        protected PromoService $promoService,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart()->load(['items.product', 'items.variant']);
        $subtotal = $this->cartService->subtotal();
        $settings = $this->storeService->settings();
        $discount = $this->promoService->discount($subtotal);

        return view('pages.cart', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'settings' => $settings,
            'deliveryPrice' => $this->deliveryService->deliveryPrice($subtotal),
            'discount' => $discount,
            'appliedPromo' => $this->promoService->applied(),
        ]);
    }

    public function applyPromo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->promoService->apply($validated['code'], $this->cartService->subtotal());

        return back()->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    public function removePromo(): RedirectResponse
    {
        $this->promoService->clear();

        return back()->with('status', 'Промо кодът е премахнат.');
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['integer', 'min:0', 'max:5'],
            'removed' => ['nullable', 'array'],
            'removed.*' => ['integer', 'exists:ingredients,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->findOrFail($validated['product_id']);
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->findOrFail($validated['product_variant_id']);

        $options = [];

        if ($product->allowsExtras() && ! empty($validated['extras'])) {
            $extraQuantities = collect($validated['extras'])
                ->map(fn ($qty) => (int) $qty)
                ->filter(fn ($qty) => $qty > 0);

            if ($extraQuantities->isNotEmpty()) {
                $ingredients = Ingredient::query()
                    ->where('is_extra', true)
                    ->where('is_active', true)
                    ->whereIn('id', $extraQuantities->keys())
                    ->get()
                    ->keyBy('id');

                foreach ($extraQuantities as $ingredientId => $quantity) {
                    $ingredient = $ingredients->get((int) $ingredientId);

                    if (! $ingredient) {
                        continue;
                    }

                    $options[] = [
                        'type' => 'extra_added',
                        'name' => $ingredient->name,
                        'price' => $ingredient->priceForVariant($variant),
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        if (! empty($validated['removed'])) {
            foreach (Ingredient::query()->whereIn('id', $validated['removed'])->get() as $ingredient) {
                $options[] = [
                    'type' => 'ingredient_removed',
                    'name' => $ingredient->name,
                    'price' => 0,
                ];
            }
        }

        $this->cartService->addItem(
            $product,
            $variant,
            $validated['quantity'] ?? 1,
            $validated['note'] ?? null,
            $options
        );

        return redirect()->route('cart')->with('status', 'Продуктът е добавен в количката.');
    }

    public function updateItem(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $this->cartService->updateItem($item, $validated['quantity']);

        return back()->with('status', 'Количката е обновена.');
    }

    public function removeItem(CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);
        $this->cartService->removeItem($item);

        return back()->with('status', 'Продуктът е премахнат.');
    }

    protected function authorizeItem(CartItem $item): void
    {
        abort_unless($item->cart_id === $this->cartService->getCart()->id, 403);
    }
}
