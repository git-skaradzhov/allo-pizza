<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            IngredientSeeder::class,
            ProductSeeder::class,
            ProductImageSeeder::class,
            BannerImageSeeder::class,
            LunchMenuItemSeeder::class,
            LunchMenuSeeder::class,
            PageSeeder::class,
            StoreImageSeeder::class,
            StoreSettingSeeder::class,
            WorkingHourSeeder::class,
            PromoCodeSeeder::class,
        ]);
    }
}
