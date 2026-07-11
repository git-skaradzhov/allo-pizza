<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('new_menu_highlights')) {
            Schema::create('new_menu_highlights', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('message')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('new_menu_highlight_product')) {
            Schema::create('new_menu_highlight_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('new_menu_highlight_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unique(['new_menu_highlight_id', 'product_id'], 'nmhp_highlight_product_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('new_menu_highlight_product');
        Schema::dropIfExists('new_menu_highlights');
    }
};
