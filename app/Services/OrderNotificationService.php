<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    public function sendOrderCreated(Order $order): void
    {
        $order->loadMissing('items.options');

        Bus::dispatchAfterResponse(function () use ($order): void {
            try {
                OrderCreated::dispatch($order);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        if (! empty($order->customer_email)) {
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $adminEmail = StoreSetting::current()->store_email;

        if (! empty($adminEmail)) {
            try {
                Mail::to($adminEmail)->send(new NewOrderAdminNotification($order));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
