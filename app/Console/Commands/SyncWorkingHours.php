<?php

namespace App\Console\Commands;

use App\Models\WorkingHour;
use Illuminate\Console\Command;

class SyncWorkingHours extends Command
{
    protected $signature = 'store:sync-hours';

    protected $description = 'Синхронизира работното време: Пон–Съб 09:00–21:00, неделя затворена';

    public function handle(): int
    {
        $schedule = [
            1 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            2 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            3 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            4 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            5 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            6 => ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
            7 => ['opens_at' => null, 'closes_at' => null, 'is_closed' => true],
        ];

        foreach ($schedule as $dayOfWeek => $hours) {
            WorkingHour::query()->updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                $hours,
            );
        }

        $this->info('Работното време е синхронизирано: Понеделник – Събота 09:00–21:00, неделя затворена.');

        return self::SUCCESS;
    }
}
