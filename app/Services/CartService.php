<?php

namespace App\Services;

use App\Enums\CartItemType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\LunchMenuItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getCart(): Cart
    {
        $customer = $this->resolveCustomer();

        if ($customer) {
            return Cart::query()->firstOrCreate(['customer_id' => $customer->id]);
        }

        if ($cartId = Session::get('cart_id')) {
            $existing = Cart::query()->whereNull('customer_id')->find($cartId);

            if ($existing) {
                return $existing;
            }
        }

        $cart = Cart::query()->firstOrCreate(['session_id' => Session::getId()]);
        Session::put('cart_id', $cart->id);

        return $cart;
    }

    public function addItem(
        Product $product,
        ProductVariant $variant,
        int $quantity = 1,
        ?string $note = null,
        array $options = []
    ): CartItem {
        $cart = $this->getCart();

        $extrasTotal = collect($options)
            ->where('type', 'extra_added')
            ->sum(fn ($option) => (float) ($option['price'] ?? 0) * (int) ($option['quantity'] ?? 1));

        $unitPrice = (float) $variant->price + (float) $extrasTotal;
        $totalPrice = $unitPrice * $quantity;

        if (empty($options) && empty($note)) {
            $item = $cart->items()
                ->where('item_type', CartItemType::Product)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->whereNull('note')
                ->where(function ($query) {
                    $query->whereNull('options')->orWhereIn('options', ['[]', 'null']);
                })
                ->first();

            if ($item) {
                $newQuantity = $item->quantity + $quantity;
                $item->update([
                    'quantity' => $newQuantity,
                    'total_price' => $newQuantity * (float) $item->unit_price,
                ]);

                return $item->fresh();
            }
        }

        return $cart->items()->create([
            'item_type' => CartItemType::Product,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'note' => $note,
            'options' => $options,
        ]);
    }

    public function addLunchItem(LunchMenuItem $item, int $quantity = 1): CartItem
    {
        $cart = $this->getCart();
        $unitPrice = (float) $item->price;
        $totalPrice = $unitPrice * $quantity;

        $existing = $cart->items()
            ->where('item_type', CartItemType::LunchItem)
            ->where('lunch_menu_item_id', $item->id)
            ->whereNull('note')
            ->first();

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;
            $existing->update([
                'quantity' => $newQuantity,
                'total_price' => $newQuantity * (float) $existing->unit_price,
            ]);

            return $existing->fresh();
        }

        return $cart->items()->create([
            'item_type' => CartItemType::LunchItem,
            'product_id' => null,
            'product_variant_id' => null,
            'lunch_menu_item_id' => $item->id,
            'item_name' => $item->name,
            'item_description' => $item->description,
            'item_image' => $item->image,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'note' => null,
            'options' => [],
        ]);
    }

    public function addLunchItems(array $items): int
    {
        $added = 0;

        foreach ($items as $entry) {
            $this->addLunchItem($entry['item'], $entry['quantity']);
            $added++;
        }

        return $added;
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return $item;
        }

        $item->update([
            'quantity' => $quantity,
            'total_price' => $quantity * (float) $item->unit_price,
        ]);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        $this->getCart()->items()->delete();
    }

    public function subtotal(): float
    {
        return (float) $this->getCart()->items()->sum('total_price');
    }

    public function itemCount(): int
    {
        return (int) $this->getCart()->items()->sum('quantity');
    }

    public function mergeGuestCartIntoCustomer(Customer $customer): void
    {
        $sessionCart = Cart::query()->where('session_id', Session::getId())->first()
            ?? Cart::query()->whereNull('customer_id')->find(Session::get('cart_id'));

        if (! $sessionCart || $sessionCart->items()->count() === 0) {
            return;
        }

        $customerCart = Cart::query()->firstOrCreate(['customer_id' => $customer->id]);

        foreach ($sessionCart->items as $guestItem) {
            $existing = $customerCart->items()
                ->where('item_type', $guestItem->item_type)
                ->when(
                    $guestItem->item_type === CartItemType::LunchItem,
                    fn ($query) => $query->where('lunch_menu_item_id', $guestItem->lunch_menu_item_id),
                    fn ($query) => $query
                        ->where('product_id', $guestItem->product_id)
                        ->where('product_variant_id', $guestItem->product_variant_id)
                )
                ->first();

            if ($existing) {
                $quantity = $existing->quantity + $guestItem->quantity;
                $existing->update([
                    'quantity' => $quantity,
                    'total_price' => $quantity * (float) $existing->unit_price,
                ]);
            } else {
                $guestItem->update(['cart_id' => $customerCart->id]);
            }
        }

        $sessionCart->delete();
        Session::forget('cart_id');
    }

    protected function resolveCustomer(): ?Customer
    {
        $user = Auth::user();

        return $user?->customer;
    }
}
