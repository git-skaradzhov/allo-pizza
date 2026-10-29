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
            $table->string('facebook_url')->nullable()->after('store_email');
            $table->string('instagram_url')->nullable()->after('facebook_url');
        });

        DB::table('store_settings')
            ->where(function ($query): void {
                $query->whereNull('facebook_url')->orWhere('facebook_url', '');
            })
            ->update([
                'facebook_url' => 'https://www.facebook.com/share/1BaUfp47Gd/',
            ]);

        DB::table('store_settings')
            ->where(function ($query): void {
                $query->whereNull('instagram_url')->orWhere('instagram_url', '');
            })
            ->update([
                'instagram_url' => 'https://www.instagram.com/allopizza_bg',
            ]);
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['facebook_url', 'instagram_url']);
        });
    }
};
