<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Models\WorkingHour;
use App\Support\DayOfWeek;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StoreService
{
    public function settings(): StoreSetting
    {
        return StoreSetting::current();
    }

    public function isOpen(?Carbon $at = null): bool
    {
        $settings = $this->settings();

        if (! $settings->is_store_open) {
            return false;
        }

        $workingHour = $this->workingHourFor($at);

        if (! $workingHour || $workingHour->is_closed) {
            return false;
        }

        if (! $workingHour->opens_at || ! $workingHour->closes_at) {
            return false;
        }

        return $this->isWithinWorkingHours($workingHour, $at ?? now());
    }

    public function workingHoursMessage(?Carbon $at = null): string
    {
        $at ??= now();
        $workingHour = $this->workingHourFor($at);

        if (! $workingHour || $workingHour->is_closed) {
            return 'Днес сме затворени. '.$this->weeklyScheduleSummary();
        }

        if (! $workingHour->opens_at || ! $workingHour->closes_at) {
            return 'Днес сме затворени. '.$this->weeklyScheduleSummary();
        }

        $opensAt = $this->formatTime($workingHour->opens_at);
        $closesAt = $this->formatTime($workingHour->closes_at);

        if ($this->isWithinWorkingHours($workingHour, $at)) {
            return sprintf('Отворено до %s ч.', $closesAt);
        }

        if ($this->currentMinutes($at) < $this->timeToMinutes($workingHour->opens_at)) {
            return sprintf('Отваряме днес в %s ч.', $opensAt);
        }

        return sprintf('Затворено · днес: %s – %s ч.', $opensAt, $closesAt);
    }

    public function storeStatusLabel(?Carbon $at = null): string
    {
        return $this->isOpen($at) ? 'Отворено' : 'Затворено';
    }

    public function storeStatusDetail(?Carbon $at = null): string
    {
        $at ??= now();

        if ($this->isOpen($at)) {
            $workingHour = $this->workingHourFor($at);

            if ($workingHour?->closes_at) {
                return sprintf('до %s ч.', $this->formatTime($workingHour->closes_at));
            }

            return '';
        }

        $nextOpening = $this->nextOpeningTime($at);

        if ($nextOpening !== null) {
            return sprintf('след %s', $nextOpening);
        }

        return $this->weeklyScheduleSummary();
    }

    public function weeklyScheduleSummary(): string
    {
        $hours = $this->weeklySchedule();

        if ($hours->isEmpty()) {
            return 'Работно време по обявление.';
        }

        $parts = [];

        $openGroups = [];
        foreach ($hours as $hour) {
            if ($hour->is_closed || ! $hour->opens_at || ! $hour->closes_at) {
                continue;
            }

            $key = $this->formatTime($hour->opens_at).' – '.$this->formatTime($hour->closes_at);
            $openGroups[$key][] = $hour->day_of_week;
        }

        foreach ($openGroups as $timeRange => $days) {
            $parts[] = $this->formatDayRange($days).': '.$timeRange.' ч.';
        }

        $closedDays = $hours
            ->filter(fn (WorkingHour $hour) => $hour->is_closed || ! $hour->opens_at || ! $hour->closes_at)
            ->pluck('day_of_week')
            ->all();

        if ($closedDays !== []) {
            $parts[] = $this->formatDayRange($closedDays).': почивен ден';
        }

        return implode(' · ', $parts);
    }

    public function ordersClosedMessage(): string
    {
        $customMessage = trim((string) $this->settings()->closed_message);

        if ($customMessage !== '') {
            return $customMessage;
        }

        return 'В момента не приемаме поръчки. '.$this->weeklyScheduleSummary();
    }

    /**
     * @return list<string>
     */
    public function schemaOpeningHours(): array
    {
        $hours = [];

        foreach ($this->groupedOpenHours() as $timeRange => $days) {
            $hours[] = $this->compactSchemaDays($days).' '.$timeRange;
        }

        return $hours;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function schemaOpeningHoursSpecification(): array
    {
        $dayCodes = $this->schemaDayCodes();
        $spec = [];

        foreach ($this->groupedOpenHours() as $timeRange => $days) {
            [$opens, $closes] = explode('-', $timeRange, 2);

            $spec[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_map(fn (int $day) => $dayCodes[$day] ?? null, $days),
                'opens' => $opens,
                'closes' => $closes,
            ];
        }

        return $spec;
    }

    public function weeklySchedule(): Collection
    {
        return WorkingHour::query()
            ->orderBy('day_of_week')
            ->get();
    }

    public function workingHourFor(?Carbon $at = null): ?WorkingHour
    {
        $at ??= now();

        return WorkingHour::query()
            ->where('day_of_week', $at->dayOfWeekIso)
            ->first();
    }

    private function nextOpeningTime(?Carbon $at = null): ?string
    {
        $at ??= now();

        for ($offset = 0; $offset < 7; $offset++) {
            $checkAt = $at->copy()->addDays($offset);
            $workingHour = $this->workingHourFor($checkAt);

            if (! $workingHour || $workingHour->is_closed || ! $workingHour->opens_at) {
                continue;
            }

            $opensMinutes = $this->timeToMinutes($workingHour->opens_at);

            if ($opensMinutes === null) {
                continue;
            }

            if ($offset === 0) {
                if ($this->currentMinutes($at) < $opensMinutes) {
                    return $this->formatTime($workingHour->opens_at);
                }

                continue;
            }

            return $this->formatTime($workingHour->opens_at);
        }

        return null;
    }

    private function formatDayRange(array $isoDays): string
    {
        $isoDays = array_values(array_unique(array_map('intval', $isoDays)));
        sort($isoDays);

        if ($isoDays === []) {
            return '';
        }

        $runs = [];
        $currentRun = [$isoDays[0]];

        for ($index = 1; $index < count($isoDays); $index++) {
            if ($isoDays[$index] === end($currentRun) + 1) {
                $currentRun[] = $isoDays[$index];
            } else {
                $runs[] = $currentRun;
                $currentRun = [$isoDays[$index]];
            }
        }

        $runs[] = $currentRun;

        return collect($runs)
            ->map(function (array $run): string {
                if (count($run) === 1) {
                    return DayOfWeek::label($run[0]);
                }

                return DayOfWeek::label($run[0]).' – '.DayOfWeek::label($run[array_key_last($run)]);
            })
            ->implode(', ');
    }

    private function isWithinWorkingHours(WorkingHour $workingHour, Carbon $at): bool
    {
        $current = $this->currentMinutes($at);
        $opens = $this->timeToMinutes($workingHour->opens_at);
        $closes = $this->timeToMinutes($workingHour->closes_at);

        if ($opens === null || $closes === null) {
            return false;
        }

        return $current >= $opens && $current <= $closes;
    }

    private function currentMinutes(Carbon $at): int
    {
        return ($at->hour * 60) + $at->minute;
    }

    private function timeToMinutes(mixed $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }

        $value = $time instanceof Carbon
            ? $time->format('H:i:s')
            : (string) $time;

        if (! preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    private function formatTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }

    /**
     * @return array<string, list<int>>
     */
    private function groupedOpenHours(): array
    {
        $groups = [];

        foreach ($this->weeklySchedule() as $hour) {
            if ($hour->is_closed || ! $hour->opens_at || ! $hour->closes_at) {
                continue;
            }

            $key = $this->formatTime($hour->opens_at).'-'.$this->formatTime($hour->closes_at);
            $groups[$key][] = (int) $hour->day_of_week;
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    private function schemaDayCodes(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * @param  list<int>  $isoDays
     */
    private function compactSchemaDays(array $isoDays): string
    {
        $isoDays = array_values(array_unique(array_map('intval', $isoDays)));
        sort($isoDays);

        $short = [
            1 => 'Mo',
            2 => 'Tu',
            3 => 'We',
            4 => 'Th',
            5 => 'Fr',
            6 => 'Sa',
            7 => 'Su',
        ];

        if ($isoDays === []) {
            return '';
        }

        $runs = [];
        $currentRun = [$isoDays[0]];

        for ($index = 1; $index < count($isoDays); $index++) {
            if ($isoDays[$index] === end($currentRun) + 1) {
                $currentRun[] = $isoDays[$index];
            } else {
                $runs[] = $currentRun;
                $currentRun = [$isoDays[$index]];
            }
        }

        $runs[] = $currentRun;

        return collect($runs)
            ->map(function (array $run) use ($short): string {
                if (count($run) === 1) {
                    return $short[$run[0]];
                }

                return $short[$run[0]].'-'.$short[$run[array_key_last($run)]];
            })
            ->implode(',');
    }
}
