<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('store_city')->nullable()->after('store_address');
        });

        foreach (DB::table('store_settings')->select('id', 'store_address', 'store_city')->get() as $setting) {
            if (filled($setting->store_city)) {
                continue;
            }

            $city = null;

            if (is_string($setting->store_address) && preg_match('/гр\.\s*([^,]+)/u', $setting->store_address, $matches)) {
                $city = trim($matches[1]);
            }

            if ($city !== null && $city !== '') {
                DB::table('store_settings')->where('id', $setting->id)->update([
                    'store_city' => $city,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('store_city');
        });
    }
};
