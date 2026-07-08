<?php

namespace App\Support;

class DayOfWeek
{
    public const ISO_LABELS = [
        1 => 'Понеделник',
        2 => 'Вторник',
        3 => 'Сряда',
        4 => 'Четвъртък',
        5 => 'Петък',
        6 => 'Събота',
        7 => 'Неделя',
    ];

    public static function isoOptions(): array
    {
        return self::ISO_LABELS;
    }

    public static function label(int $isoDay): string
    {
        return self::ISO_LABELS[$isoDay] ?? (string) $isoDay;
    }
}
