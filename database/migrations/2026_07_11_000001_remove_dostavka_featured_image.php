<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Page::query()
            ->where('slug', 'dostavka')
            ->update(['featured_image' => null]);
    }

    public function down(): void
    {
        Page::query()
            ->where('slug', 'dostavka')
            ->update(['featured_image' => 'store/exterior-side.png']);
    }
};
