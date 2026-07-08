<?php

namespace Tests\Unit;

use App\Models\StoreSetting;
use App\Models\WorkingHour;
use App\Services\StoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::query()->create([
            'store_name' => 'Allo! Pizza',
            'is_store_open' => true,
        ]);
    }

    public function test_store_is_closed_before_opening_hours(): void
    {
        $this->seedWorkingDay(opensAt: '09:00:00', closesAt: '21:00:00');

        $service = app(StoreService::class);
        $at = Carbon::parse('2026-07-08 02:52:00', 'Europe/Sofia');

        $this->assertFalse($service->isOpen($at));
        $this->assertSame('Отваряме днес в 09:00 ч.', $service->workingHoursMessage($at));
    }

    public function test_store_is_open_during_working_hours(): void
    {
        $this->seedWorkingDay(opensAt: '09:00:00', closesAt: '21:00:00');

        $service = app(StoreService::class);
        $at = Carbon::parse('2026-07-08 14:52:00', 'Europe/Sofia');

        $this->assertTrue($service->isOpen($at));
        $this->assertSame('Отворено до 21:00 ч.', $service->workingHoursMessage($at));
    }

    public function test_store_is_closed_after_working_hours(): void
    {
        $this->seedWorkingDay(opensAt: '09:00:00', closesAt: '21:00:00');

        $service = app(StoreService::class);
        $at = Carbon::parse('2026-07-08 22:15:00', 'Europe/Sofia');

        $this->assertFalse($service->isOpen($at));
        $this->assertSame('Затворено · днес: 09:00 – 21:00 ч.', $service->workingHoursMessage($at));
    }

    public function test_weekly_schedule_summary_is_built_from_database(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $day) {
            WorkingHour::query()->create([
                'day_of_week' => $day,
                'opens_at' => '09:00:00',
                'closes_at' => '21:00:00',
                'is_closed' => false,
            ]);
        }

        WorkingHour::query()->create([
            'day_of_week' => 7,
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);

        $summary = app(StoreService::class)->weeklyScheduleSummary();

        $this->assertSame(
            'Понеделник – Събота: 09:00 – 21:00 ч. · Неделя: почивен ден',
            $summary,
        );
    }

    public function test_sunday_uses_iso_day_seven(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $day) {
            WorkingHour::query()->create([
                'day_of_week' => $day,
                'opens_at' => '09:00:00',
                'closes_at' => '21:00:00',
                'is_closed' => false,
            ]);
        }

        WorkingHour::query()->create([
            'day_of_week' => 7,
            'opens_at' => '09:00:00',
            'closes_at' => '21:00:00',
            'is_closed' => true,
        ]);

        $service = app(StoreService::class);
        $at = Carbon::parse('2026-07-12 12:00:00', 'Europe/Sofia');

        $this->assertFalse($service->isOpen($at));
        $this->assertStringContainsString('Понеделник – Събота: 09:00 – 21:00 ч.', $service->workingHoursMessage($at));
    }

    private function seedWorkingDay(string $opensAt, string $closesAt): void
    {
        WorkingHour::query()->create([
            'day_of_week' => 3,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'is_closed' => false,
        ]);
    }
}
