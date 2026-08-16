<?php

namespace App\Services\Meta;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaPurchaseTracker
{
    public function __construct(
        protected MetaConsentService $consentService,
        protected MetaConversionsApiService $conversionsApi,
        protected MetaEventFactory $eventFactory,
        protected MetaUserDataBuilder $userDataBuilder,
        protected MetaPageEventRegistrar $pageEvents,
    ) {}

    public function trackAddToCart(CartItem $item, int $addedQuantity, ?Request $request = null): void
    {
        $request ??= request();

        if (! $this->consentService->hasGranted($request)) {
            return;
        }

        try {
            $eventId = $this->eventFactory->newEventId();
            $customData = $this->eventFactory->addToCartCustomData($item, $addedQuantity);
            $event = $this->eventFactory->makeEvent(
                'AddToCart',
                $eventId,
                $this->sourceUrl($request),
                $customData,
                $this->userDataBuilder->fromRequest($request),
            );

            $this->conversionsApi->send($event);
            $this->flashBrowserEvent('AddToCart', $eventId, $customData);
        } catch (Throwable $exception) {
            $this->reportSafely($exception);
        }
    }

    /**
     * Send Purchase after a successful DB commit.
     *
     * Extension point: when online payment is added, call this only after
     * the payment is confirmed — not when the unpaid order is first inserted.
     * Cash / pay-at-store orders with status New are treated as accepted.
     */
    public function trackPurchaseAfterCommit(Order $order, bool $flashBrowserEvent = true, ?Request $request = null): void
    {
        $request ??= request();

        if (! $this->consentService->hasGranted($request)) {
            return;
        }

        try {
            $order->loadMissing('items');
            $eventId = $this->eventFactory->purchaseEventId($order);

            if ($order->meta_purchase_event_id !== $eventId) {
                $order->forceFill([
                    'meta_purchase_event_id' => $eventId,
                ])->save();
            }

            $sent = $this->sendPurchase($order, $request, enforceConsent: true);

            if ($flashBrowserEvent) {
                $this->flashBrowserEvent(
                    'Purchase',
                    $eventId,
                    $this->eventFactory->purchaseCustomData($order),
                );
            }

            if (! $sent) {
                return;
            }
        } catch (Throwable $exception) {
            $this->recordPurchaseError($order, $this->conversionsApi->sanitize($exception->getMessage()) ?? 'exception');
            $this->reportSafely($exception);
        }
    }

    public function retryPurchase(Order $order): bool
    {
        if ($order->meta_purchase_sent_at !== null) {
            return true;
        }

        if ($order->meta_purchase_event_id === null) {
            return false;
        }

        $maxAttempts = (int) config('meta.capi.max_purchase_attempts', 10);

        if ((int) $order->meta_purchase_attempts >= $maxAttempts) {
            return false;
        }

        $order->loadMissing('items');

        return $this->sendPurchase($order, enforceConsent: false);
    }

    public function sendPurchase(Order $order, ?Request $request = null, bool $enforceConsent = true): bool
    {
        $request ??= request();
        $eventId = $order->meta_purchase_event_id ?: $this->eventFactory->purchaseEventId($order);

        if ($this->pageEvents->wasEventSent($eventId) && $order->meta_purchase_sent_at !== null) {
            return true;
        }

        if (! $this->conversionsApi->isConfigured()) {
            return false;
        }

        $event = $this->eventFactory->makeEvent(
            'Purchase',
            $eventId,
            $this->sourceUrl($request, route('checkout')),
            $this->eventFactory->purchaseCustomData($order),
            $this->userDataBuilder->fromOrder($order, $request),
        );

        $sent = $this->conversionsApi->send($event, $enforceConsent);

        if ($sent) {
            $order->forceFill([
                'meta_purchase_event_id' => $eventId,
                'meta_purchase_sent_at' => now(),
                'meta_purchase_last_error' => null,
            ])->save();
            $this->pageEvents->markEventSent($eventId);

            return true;
        }

        $this->recordPurchaseError($order, 'capi_request_failed');

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function flashBrowserEvent(string $eventName, string $eventId, array $payload): void
    {
        $events = session()->get('meta_browser_events', []);
        $events[] = [
            'event_name' => $eventName,
            'event_id' => $eventId,
            'payload' => $payload,
        ];

        session()->flash('meta_browser_events', $events);
    }

    protected function sourceUrl(Request $request, ?string $fallback = null): string
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $referer;
        }

        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }

        return $request->fullUrl();
    }

    protected function recordPurchaseError(Order $order, string $error): void
    {
        try {
            $order->forceFill([
                'meta_purchase_attempts' => (int) $order->meta_purchase_attempts + 1,
                'meta_purchase_last_error' => mb_substr($error, 0, 1000),
            ])->save();
        } catch (Throwable $exception) {
            $this->reportSafely($exception);
        }
    }

    protected function reportSafely(Throwable $exception): void
    {
        $message = $this->conversionsApi->sanitize($exception->getMessage()) ?? 'Meta tracking error.';

        Log::warning('Meta tracking failed without blocking the customer flow.', [
            'error' => $message,
        ]);
    }
}
