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
            'pepsi-500' => 'pepsi-500.png',
            'pepsi-zero-500' => 'pepsi-zero-500.png',
            'mirinda-portokal-500' => 'mirinda-portokal-500.png',
            'mirinda-limon-500' => 'mirinda-limon-500.png',
            'mirinda-ananas-500' => 'mirinda-ananas-500.png',
            'evervess-tonik-500' => 'evervess-tonik-500.png',
            'prisun-gorski-plodove-500' => 'prisun-gorski-plodove-500.png',
            'prisun-yabalka-500' => 'prisun-yabalka-500.png',
            'velingrad-gazirana-500' => 'velingrad-gazirana-500.png',
            'rilana-izvorna-500' => 'rilana-izvorna-500.png',
            'pepsi-1000' => 'pepsi-1000.png',
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
