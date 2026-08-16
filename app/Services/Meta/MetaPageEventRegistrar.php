<?php

namespace App\Services\Meta;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetaPageEventRegistrar
{
    /**
     * @var list<array<string, mixed>>
     */
    protected array $events = [];

    protected bool $pageViewQueued = false;

    public function __construct(
        protected MetaEventFactory $eventFactory,
        protected MetaEventTokenService $tokenService,
    ) {}

    public function queuePageView(Request $request): void
    {
        if ($this->pageViewQueued) {
            return;
        }

        $this->pageViewQueued = true;
        $eventId = $this->eventFactory->newEventId();
        $url = $request->fullUrl();

        array_unshift($this->events, $this->makeBrowserEvent(
            'PageView',
            $eventId,
            $url,
            [],
        ));
    }

    public function queueViewContent(Product $product, Request $request): void
    {
        $customData = $this->eventFactory->viewContentCustomData($product);

        $this->events[] = $this->makeBrowserEvent(
            'ViewContent',
            $this->eventFactory->newEventId(),
            $request->fullUrl(),
            $customData,
        );
    }

    /**
     * @param  array{subtotal: float, totalDiscount: float}  $pricing
     */
    public function queueInitiateCheckout(Cart $cart, array $pricing, Request $request): void
    {
        $customData = $this->eventFactory->initiateCheckoutCustomData($cart, $pricing);

        $this->events[] = $this->makeBrowserEvent(
            'InitiateCheckout',
            $this->eventFactory->newEventId(),
            $request->fullUrl(),
            $customData,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function browserEvents(): array
    {
        return $this->events;
    }

    public function markEventSent(string $eventId): void
    {
        Cache::put($this->sentCacheKey($eventId), true, (int) config('meta.event_dedupe_ttl', 86400));
    }

    public function wasEventSent(string $eventId): bool
    {
        return Cache::has($this->sentCacheKey($eventId));
    }

    /**
     * @param  array<string, mixed>  $customData
     * @return array<string, mixed>
     */
    protected function makeBrowserEvent(string $eventName, string $eventId, string $eventSourceUrl, array $customData): array
    {
        return [
            'event_name' => $eventName,
            'event_id' => $eventId,
            'token' => $this->tokenService->encode($eventName, $eventId, $eventSourceUrl, $customData),
            'payload' => $customData,
        ];
    }

    protected function sentCacheKey(string $eventId): string
    {
        return 'meta_capi_sent:'.$eventId;
    }
}
