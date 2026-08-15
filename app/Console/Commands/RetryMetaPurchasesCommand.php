<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Meta\MetaConversionsApiService;
use App\Services\Meta\MetaPurchaseTracker;
use Illuminate\Console\Command;

class RetryMetaPurchasesCommand extends Command
{
    protected $signature = 'meta:retry-purchases {--limit=50 : Maximum number of orders to retry}';

    protected $description = 'Retry unsent Meta Conversions API Purchase events';

    public function handle(MetaPurchaseTracker $tracker, MetaConversionsApiService $conversionsApi): int
    {
        if (! $conversionsApi->isConfigured()) {
            $this->warn('Meta CAPI is not configured. Nothing to retry.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $maxAttempts = (int) config('meta.capi.max_purchase_attempts', 10);

        $orders = Order::query()
            ->whereNotNull('meta_purchase_event_id')
            ->whereNull('meta_purchase_sent_at')
            ->where('meta_purchase_attempts', '<', $maxAttempts)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No Purchase events waiting for retry.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($orders as $order) {
            if ($tracker->retryPurchase($order)) {
                $sent++;

                continue;
            }

            $failed++;
        }

        $this->info("Retried {$orders->count()} order(s): {$sent} sent, {$failed} failed.");

        return self::SUCCESS;
    }
}
