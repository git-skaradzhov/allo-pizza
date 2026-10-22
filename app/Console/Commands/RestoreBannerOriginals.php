<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreBannerOriginals extends Command
{
    protected $signature = 'banners:restore-originals';

    protected $description = 'Point banner image columns back at original files instead of sized WebP variants';

    public function handle(): int
    {
        return $this->call('banners:optimize-images', [
            '--restore-only' => true,
        ]);
    }
}
