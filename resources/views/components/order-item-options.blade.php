@props(['options' => []])

@php
    $optionList = $options instanceof \Illuminate\Support\Collection ? $options->all() : (array) $options;
@endphp

@if (count($optionList) > 0)
    <div {{ $attributes->merge(['class' => 'mt-0.5 space-y-0.5']) }}>
        @foreach ($optionList as $option)
            @php
                if ($option instanceof \App\Models\OrderItemOption) {
                    $type = $option->option_type->value;
                    $name = $option->name;
                    $lineTotal = (float) $option->price;
                    $quantity = 1;
                } else {
                    $type = $option['type'] ?? '';
                    $name = $option['name'] ?? '';
                    $unitPrice = (float) ($option['price'] ?? 0);
                    $quantity = (int) ($option['quantity'] ?? 1);
                    $lineTotal = $unitPrice * $quantity;
                }
                $isRemoved = $type === 'ingredient_removed';
                $isExtra = $type === 'extra_added';
            @endphp
            <p class="text-xs {{ $isRemoved ? 'text-stone-400 line-through' : 'text-stone-500' }}">
                {{ $isExtra ? '+ ' : ($isRemoved ? '− ' : '') }}{{ $name }}
                @if ($isExtra && $quantity > 1 && ! ($option instanceof \App\Models\OrderItemOption))
                    <span class="text-stone-400">×{{ $quantity }}</span>
                @endif
                @if ($isExtra && $lineTotal > 0)
                    <span class="text-stone-400">({{ money($lineTotal) }})</span>
                @endif
            </p>
        @endforeach
    </div>
@endif
