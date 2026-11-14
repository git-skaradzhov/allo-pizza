<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingOrdersPingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->isAdministrator()) {
            return response()->json([
                'ok' => false,
                'reason' => 'unauthenticated',
            ], 401);
        }

        $orderIds = Order::query()
            ->where('status', OrderStatus::New)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'orderIds' => $orderIds,
        ]);
    }
}
