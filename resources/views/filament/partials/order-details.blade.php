<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
        <p class="font-semibold text-gray-950 dark:text-white">{{ $order->customer_name }}</p>
        <p class="mt-1">
            <a href="tel:{{ $order->customer_phone }}" class="text-primary-600 hover:underline dark:text-primary-400">
                {{ $order->customer_phone }}
            </a>
        </p>
        @if ($order->customer_email)
            <p class="mt-1">{{ $order->customer_email }}</p>
        @endif
        <p class="mt-2">
            <span class="font-medium">Получаване:</span> {{ $order->delivery_type->label() }}
        </p>
        @if ($order->delivery_address)
            <p class="mt-1">
                <span class="font-medium">Адрес:</span> {{ $order->delivery_address }}
            </p>
        @endif
        <p class="mt-1">
            <span class="font-medium">Плащане:</span> {{ $order->payment_method->label() }}
        </p>
        @if ($order->customer_note)
            <p class="mt-2 rounded-md bg-amber-50 p-2 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                <span class="font-medium">Бележка:</span> {{ $order->customer_note }}
            </p>
        @endif
    </div>

    <div>
        <h3 class="mb-2 font-semibold text-gray-950 dark:text-white">Артикули</h3>
        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach ($order->items as $item)
                <li class="flex items-start justify-between gap-4 px-4 py-3">
                    <div>
                        <p class="font-medium text-gray-950 dark:text-white">
                            {{ $item->product_name }}
                            @if ($item->variant_name)
                                <span class="font-normal text-gray-500 dark:text-gray-400">({{ $item->variant_name }})</span>
                            @endif
                            <span class="text-gray-500 dark:text-gray-400">× {{ $item->quantity }}</span>
                        </p>
                        @foreach ($item->options as $option)
                            <p @class([
                                'text-xs',
                                'text-gray-500 line-through dark:text-gray-400' => $option->option_type->value === 'ingredient_removed',
                                'text-gray-600 dark:text-gray-300' => $option->option_type->value !== 'ingredient_removed',
                            ])>
                                {{ $option->option_type->value === 'extra_added' ? '+ ' : '− ' }}{{ $option->name }}
                                @if ($option->option_type->value === 'extra_added' && (float) $option->price > 0)
                                    ({{ money($option->price) }})
                                @endif
                            </p>
                        @endforeach
                        @if ($item->note)
                            <p class="mt-1 text-xs italic text-gray-500 dark:text-gray-400">„{{ $item->note }}"</p>
                        @endif
                    </div>
                    <p class="shrink-0 font-medium text-gray-950 dark:text-white">{{ money($item->total_price) }}</p>
                </li>
            @endforeach
        </ul>
    </div>

    <dl class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
        <dt class="text-gray-600 dark:text-gray-400">Междинна сума</dt>
        <dd class="text-right font-medium">{{ money($order->subtotal) }}</dd>

        @if ((float) $order->discount > 0)
            <dt class="text-gray-600 dark:text-gray-400">
                Отстъпка@if ($order->promo_code) ({{ $order->promo_code }})@endif
            </dt>
            <dd class="text-right font-medium">−{{ money($order->discount) }}</dd>
        @endif

        <dt class="text-gray-600 dark:text-gray-400">Доставка</dt>
        <dd @class([
            'text-right font-medium',
            'text-amber-700 dark:text-amber-300' => $order->delivery_quote_required,
        ])>{{ $order->deliveryFeeLabel() }}</dd>

        <dt class="text-lg font-bold text-gray-950 dark:text-white">Общо</dt>
        <dd class="text-right text-lg font-bold text-primary-600 dark:text-primary-400">{{ money($order->total) }}</dd>
    </dl>
</div>
