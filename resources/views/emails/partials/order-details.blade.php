<table style="width:100%;border-collapse:collapse;margin-top:16px;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;vertical-align:top;">
                <strong>{{ $item->product_name }}</strong>
                @if ($item->variant_name)
                    <span style="color:#a8a29e;">({{ $item->variant_name }})</span>
                @endif
                × {{ $item->quantity }}
                @foreach ($item->options as $option)
                    <br>
                    <span style="font-size:12px;color:{{ $option->option_type->value === 'ingredient_removed' ? '#a8a29e' : '#57534e' }};{{ $option->option_type->value === 'ingredient_removed' ? 'text-decoration:line-through;' : '' }}">
                        {{ $option->option_type->value === 'extra_added' ? '+ ' : '− ' }}{{ $option->name }}
                        @if ($option->option_type->value === 'extra_added' && (float) $option->price > 0)
                            ({{ money($option->price) }})
                        @endif
                    </span>
                @endforeach
                @if ($item->note)
                    <br><span style="font-size:12px;color:#a8a29e;font-style:italic;">„{{ $item->note }}"</span>
                @endif
            </td>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;text-align:right;white-space:nowrap;vertical-align:top;">
                {{ money($item->total_price) }}
            </td>
        </tr>
    @endforeach
</table>

<table style="width:100%;border-collapse:collapse;margin-top:16px;">
    <tr>
        <td style="padding:4px 0;color:#57534e;">Междинна сума</td>
        <td style="padding:4px 0;text-align:right;">{{ money($order->subtotal) }}</td>
    </tr>
    @if ((float) $order->discount > 0)
        <tr>
            <td style="padding:4px 0;color:#57534e;">
                Отстъпка@if ($order->promo_code) ({{ $order->promo_code }})@endif
            </td>
            <td style="padding:4px 0;text-align:right;">−{{ money($order->discount) }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0;color:#57534e;">Доставка</td>
        <td style="padding:4px 0;text-align:right;">{{ $order->deliveryFeeLabel() }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0;font-weight:bold;font-size:16px;">Общо</td>
        <td style="padding:8px 0;text-align:right;font-weight:bold;font-size:16px;color:#EB1C22;">{{ money($order->total) }}</td>
    </tr>
</table>

<div style="margin-top:16px;padding:16px;background:#faf8f2;border-radius:12px;font-size:14px;color:#57534e;">
    <p style="margin:0 0 4px;"><strong>Клиент:</strong> {{ $order->customer_name }}</p>
    <p style="margin:0 0 4px;"><strong>Телефон:</strong> {{ $order->customer_phone }}</p>
    @if ($order->customer_email)
        <p style="margin:0 0 4px;"><strong>Имейл:</strong> {{ $order->customer_email }}</p>
    @endif
    <p style="margin:0 0 4px;"><strong>Получаване:</strong> {{ $order->delivery_type->label() }}</p>
    @if ($order->delivery_address)
        <p style="margin:0 0 4px;"><strong>Адрес:</strong> {{ $order->delivery_address }}</p>
    @endif
    <p style="margin:0 0 4px;"><strong>Плащане:</strong> {{ $order->payment_method->label() }}</p>
    @if ($order->customer_note)
        <p style="margin:0;"><strong>Бележка:</strong> {{ $order->customer_note }}</p>
    @endif
</div>
