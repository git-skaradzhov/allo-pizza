<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasIsoSunday = DB::table('working_hours')->where('day_of_week', 7)->exists();
        $legacySunday = DB::table('working_hours')->where('day_of_week', 0);

        if ($legacySunday->exists()) {
            if ($hasIsoSunday) {
                $legacySunday->delete();
            } else {
                $legacySunday->update(['day_of_week' => 7]);
            }
        }
    }

    public function down(): void
    {
        // No safe automatic rollback for day remapping.
    }
};
