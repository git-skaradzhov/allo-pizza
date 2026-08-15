<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('meta_purchase_event_id')->nullable()->unique()->after('admin_note');
            $table->timestamp('meta_purchase_sent_at')->nullable()->after('meta_purchase_event_id');
            $table->unsignedInteger('meta_purchase_attempts')->default(0)->after('meta_purchase_sent_at');
            $table->text('meta_purchase_last_error')->nullable()->after('meta_purchase_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['meta_purchase_event_id']);
            $table->dropColumn([
                'meta_purchase_event_id',
                'meta_purchase_sent_at',
                'meta_purchase_attempts',
                'meta_purchase_last_error',
            ]);
        });
    }
};
