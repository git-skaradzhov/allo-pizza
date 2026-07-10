<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductImageProcessor;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            'margarita' => 'margarita.png',
            'peperoni' => 'peperoni.png',
            '4-sirena' => '4-sirena.png',
            'kaprichoza' => 'kaprichoza.png',
            'krudo' => 'krudo.png',
            'chikan' => 'chikan.png',
            'elena' => 'elena.png',
            'redzhina' => 'redzhina.png',
            'barbekyu' => 'barbekyu.png',
            'italianski-sandvich' => 'italianski-sandvich.png',
            'kapreze-sandvich' => 'kapreze-sandvich.png',
            'parlenka-sas-sirene' => 'parlenka-sas-sirene.png',
            'parlenka-s-maslo' => 'parlenka-sas-sirene.png',
            'parlenka-s-kashkaval' => 'parlenka-sas-sirene.png',
            'chesnova-parlenka' => 'parlenka-sas-sirene.png',
            'parlenka-kombinirana' => 'parlenka-kombinirana.png',
        ];

        $processor = app(ProductImageProcessor::class);

        foreach ($images as $slug => $filename) {
            $source = database_path("seeders/assets/products/{$filename}");

            if (! is_file($source)) {
                continue;
            }

            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                continue;
            }

            $storedPath = $processor->storeFromPath($product, $source);

            $product->forceFill(['image' => $storedPath])->saveQuietly();
        }
    }
}
