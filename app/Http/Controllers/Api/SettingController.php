<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreSettingResource;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => new StoreSettingResource($this->storeService->settings()),
            'is_open' => $this->storeService->isOpen(),
            'working_hours_message' => $this->storeService->workingHoursMessage(),
            'weekly_schedule_summary' => $this->storeService->weeklyScheduleSummary(),
        ]);
    }
}
