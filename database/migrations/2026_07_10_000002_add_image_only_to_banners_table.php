<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('banners', 'image_only')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->boolean('image_only')->default(false)->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'image_only')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropColumn('image_only');
            });
        }
    }
};
