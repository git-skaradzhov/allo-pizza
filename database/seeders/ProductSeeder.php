<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Support\IngredientCatalogSync;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $pizzaCategory = Category::query()->where('slug', 'pizza')->first();
        $sandviciParlenkiCategory = Category::query()->where('slug', 'sandvici-i-pierlenki')->first();
        $drinksCategory = Category::query()->where('slug', 'drinks')->first();

        if (! $pizzaCategory) {
            return;
        }

        $variants = [
            ['name' => '30 см', 'size_label' => '30 см', 'weight' => '500 гр', 'diameter' => 30, 'extra_price_multiplier' => 1.00, 'sort_order' => 1],
            ['name' => '45 см', 'size_label' => '45 см', 'weight' => '1000 гр', 'diameter' => 45, 'extra_price_multiplier' => 1.50, 'sort_order' => 2],
        ];

        $pizzas = [
            [
                'name' => 'Маргарита',
                'slug' => 'margarita',
                'short_description' => 'Доматен сос, моцарела, босилек, пармезан и зехтин.',
                'description' => 'Класическа пица с доматен сос, моцарела топка, босилек, пармезан и зехтин.',
                'prices' => ['30' => 6.9, '45' => 12.5],
                'is_featured' => true,
            ],
            [
                'name' => 'Пеперони',
                'slug' => 'peperoni',
                'short_description' => 'Доматен сос, моцарела и пеперони.',
                'description' => 'Пица с доматен сос, моцарела и пеперони.',
                'prices' => ['30' => 8.4, '45' => 15.1],
                'is_featured' => true,
                'is_spicy' => true,
            ],
            [
                'name' => '4 сирена',
                'slug' => '4-sirena',
                'short_description' => 'Сметана/доматен сос, моцарела, топено, синьо сирене и пармезан.',
                'description' => 'Пица със сметана или доматен сос, моцарела, топено сирене, синьо сирене и пармезан.',
                'prices' => ['30' => 8.4, '45' => 15.1],
                'is_featured' => true,
            ],
            [
                'name' => 'Капричоза',
                'slug' => 'kaprichoza',
                'short_description' => 'Доматен сос, моцарела, шунка, гъби и чушка.',
                'description' => 'Пица с доматен сос, моцарела, шунка, гъби и чушка.',
                'prices' => ['30' => 8.3, '45' => 14.9],
                'is_featured' => true,
            ],
            [
                'name' => 'Крудо',
                'slug' => 'krudo',
                'short_description' => 'Доматен сос, моцарела, прошуто крудо, рукола, домат чери, пармезан и балсамико.',
                'description' => 'Пица с доматен сос, моцарела, прошуто крудо, рукола, домат чери, пармезан и балсамико.',
                'prices' => ['30' => 8.6, '45' => 15.5],
                'is_featured' => true,
            ],
            [
                'name' => 'Чикън',
                'slug' => 'chikan',
                'short_description' => 'Доматен сос, моцарела, пилешко филе, чедър и пармезан.',
                'description' => 'Пица с доматен сос, моцарела, пилешко филе, чедър и пармезан.',
                'prices' => ['30' => 8.4, '45' => 15.1],
                'is_featured' => true,
            ],
            [
                'name' => 'Елена',
                'slug' => 'elena',
                'short_description' => 'Сметана, моцарела, еленски бут, манатарки, пармезан, мащерка, трюфел и салата микс.',
                'description' => 'Пица със сметана, моцарела, еленски бут, манатарки, пармезан, мащерка, трюфел и свежа салата микс.',
                'prices' => ['30' => 8.9],
            ],
            [
                'name' => 'Реджина',
                'slug' => 'redzhina',
                'short_description' => 'Доматен сос, моцарела, шунка, топено сирене и маслини.',
                'description' => 'Пица с доматен сос, моцарела, шунка, топено сирене и маслини.',
                'prices' => ['30' => 8.4, '45' => 15.1],
            ],
            [
                'name' => 'Барбекю',
                'slug' => 'barbekyu',
                'short_description' => 'Доматен сос, моцарела, пилешко филе, пушен бекон, карамелизиран лук и сос барбекю.',
                'description' => 'Пица с доматен сос, моцарела, пилешко филе, пушен бекон, карамелизиран лук и сос барбекю.',
                'prices' => ['30' => 8.6, '45' => 15.5],
            ],
        ];

        foreach ($pizzas as $index => $pizzaData) {
            $basePrice = (float) reset($pizzaData['prices']);

            $product = Product::query()->updateOrCreate(
                ['slug' => $pizzaData['slug']],
                [
                    'category_id' => $pizzaCategory->id,
                    'name' => $pizzaData['name'],
                    'short_description' => $pizzaData['short_description'],
                    'description' => $pizzaData['description'],
                    'base_price' => $basePrice,
                    'is_active' => true,
                    'is_featured' => $pizzaData['is_featured'] ?? false,
                    'is_promo' => $pizzaData['is_promo'] ?? false,
                    'is_new' => $pizzaData['is_new'] ?? false,
                    'is_spicy' => $pizzaData['is_spicy'] ?? false,
                    'sort_order' => $index + 1,
                    'seo_title' => $pizzaData['name'].' | Allo! Pizza',
                    'seo_description' => $pizzaData['short_description'],
                    'allows_extras' => null,
                ]
            );

            $product->variants()->update(['is_active' => false]);

            foreach ($variants as $variant) {
                $sizeKey = (string) $variant['diameter'];
                $price = $pizzaData['prices'][$sizeKey] ?? null;

                if ($price === null) {
                    $product->variants()
                        ->where('size_label', $variant['size_label'])
                        ->update(['is_active' => false]);

                    continue;
                }

                $product->variants()->updateOrCreate(
                    [
                        'name' => $variant['name'],
                        'size_label' => $variant['size_label'],
                    ],
                    [
                        'price' => $price,
                        'extra_price_multiplier' => $variant['extra_price_multiplier'] ?? 1.00,
                        'weight' => $variant['weight'],
                        'diameter' => $variant['diameter'],
                        'is_active' => true,
                        'sort_order' => $variant['sort_order'],
                    ]
                );
            }

            $product->ingredients()->sync([]);

            $activeSizeLabels = collect($variants)
                ->filter(fn (array $variant) => isset($pizzaData['prices'][(string) $variant['diameter']]))
                ->pluck('size_label')
                ->all();

            $product->variants()
                ->whereNotIn('size_label', $activeSizeLabels)
                ->update(['is_active' => false]);
        }

        Product::query()
            ->where('category_id', $pizzaCategory->id)
            ->whereNotIn('slug', collect($pizzas)->pluck('slug'))
            ->update(['is_active' => false]);

        $this->seedSimpleProducts($sandviciParlenkiCategory, [
            [
                'name' => 'Италиански',
                'slug' => 'italianski-sandvich',
                'short_description' => 'Пица хлебче, пилешко филе, прошуто котто, моцарела, чедър, домат, салата и айоли.',
                'description' => 'Сандвич 400 гр с пица хлебче, пилешко филе, прошуто котто, моцарела, чедър, пресен домат, салата микс, дресинг и сос айоли.',
                'base_price' => 4.9,
                'size_label' => '400 гр',
                'allows_extras' => true,
            ],
            [
                'name' => 'Капрезе',
                'slug' => 'kapreze-sandvich',
                'short_description' => 'Пица хлебче, моцарела, домат, салата, дресинг, песто и зехтин.',
                'description' => 'Сандвич 400 гр с пица хлебче, моцарела, пресен домат, салата микс, дресинг, босилково песто и зехтин.',
                'base_price' => 4.2,
                'size_label' => '400 гр',
                'allows_extras' => true,
            ],
            [
                'name' => 'Пърленка с масло',
                'slug' => 'parlenka-s-maslo',
                'short_description' => 'Топла пърленка с масло и шарена сол.',
                'description' => 'Мека пърленка, изпечена на момента и намазана с масло.',
                'base_price' => 3.90,
                'allows_extras' => false,
            ],
            [
                'name' => 'Пърленка с кашкавал',
                'slug' => 'parlenka-s-kashkaval',
                'short_description' => 'Пухкава пърленка с разтопен кашкавал.',
                'description' => 'Любима добавка към всяка пица или салата.',
                'base_price' => 4.90,
                'allows_extras' => false,
            ],
            [
                'name' => 'Чеснова пърленка',
                'slug' => 'chesnova-parlenka',
                'short_description' => 'Пърленка с чесново масло и подправки.',
                'description' => 'Ароматна чеснова пърленка, подходяща за споделяне.',
                'base_price' => 4.50,
                'allows_extras' => false,
            ],
            [
                'name' => 'Пърленка със сирене',
                'slug' => 'parlenka-sas-sirene',
                'short_description' => 'Пърленка с бяло сирене и масло.',
                'description' => 'Топла пърленка с натрошено бяло сирене и масло.',
                'base_price' => 4.70,
                'allows_extras' => false,
            ],
            [
                'name' => 'Пърленка комбинирана',
                'slug' => 'parlenka-kombinirana',
                'short_description' => 'Кашкавал, сирене и чесново масло.',
                'description' => 'Богата пърленка с кашкавал, сирене и чесново масло.',
                'base_price' => 5.90,
                'is_promo' => true,
                'allows_extras' => false,
            ],
        ]);

        $drinks = [
            [
                'name' => 'Пепси 0,5 л',
                'slug' => 'pepsi-500',
                'short_description' => 'Газирана напитка Pepsi, 0,5 л.',
                'description' => 'Пепси 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2018/05/Pepsi0.5l.png',
            ],
            [
                'name' => 'Пепси Zero 0,5 л',
                'slug' => 'pepsi-zero-500',
                'short_description' => 'Газирана напитка Pepsi Zero, 0,5 л.',
                'description' => 'Пепси Zero 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2018/05/500.pz_.png',
            ],
            [
                'name' => 'Mirinda портокал 0,5 л',
                'slug' => 'mirinda-portokal-500',
                'short_description' => 'Газирана напитка Mirinda с вкус на портокал, 0,5 л.',
                'description' => 'Mirinda портокал 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2018/06/500mO.png',
            ],
            [
                'name' => 'Mirinda лимон 0,5 л',
                'slug' => 'mirinda-limon-500',
                'short_description' => 'Газирана напитка Mirinda с вкус на лимон, 0,5 л.',
                'description' => 'Mirinda лимон 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2024/05/500ml.png',
            ],
            [
                'name' => 'Mirinda ананас 0,5 л',
                'slug' => 'mirinda-ananas-500',
                'short_description' => 'Газирана напитка Mirinda с вкус на ананас, 0,5 л.',
                'description' => 'Mirinda ананас 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2026/05/Mirinda_05L_Pineapple.png',
            ],
            [
                'name' => 'Evervess тоник 0,5 л',
                'slug' => 'evervess-tonik-500',
                'short_description' => 'Газиран тоник Evervess, 0,5 л.',
                'description' => 'Evervess тоник 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2018/05/500evt.png',
            ],
            [
                'name' => 'Prisun горски плодове 0,5 л',
                'slug' => 'prisun-gorski-plodove-500',
                'short_description' => 'Негазирана напитка Prisun с вкус на горски плодове, 0,5 л.',
                'description' => 'Prisun горски плодове 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2024/05/500pff.png',
            ],
            [
                'name' => 'Prisun ябълка 0,5 л',
                'slug' => 'prisun-yabalka-500',
                'short_description' => 'Негазирана напитка Prisun с вкус на ябълка, 0,5 л.',
                'description' => 'Prisun ябълка 0,5 л.',
                'base_price' => 1.30,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2024/05/500pa.png',
            ],
            [
                'name' => 'Газирана вода Велинград 0,5 л',
                'slug' => 'velingrad-gazirana-500',
                'short_description' => 'Газирана минерална вода Велинград, 0,5 л.',
                'description' => 'Газирана вода Велинград 0,5 л.',
                'base_price' => 1.00,
                'size_label' => '0,5 л',
                'image' => 'https://www.velingradvoda.bg/assets/upload/files/3d-model-vgcarbonated-05l-bg-bubbles.jpg',
            ],
            [
                'name' => 'Изворна вода Rilana 0,5 л',
                'slug' => 'rilana-izvorna-500',
                'short_description' => 'Изворна вода Rilana, 0,5 л.',
                'description' => 'Изворна вода Rilana 0,5 л.',
                'base_price' => 0.80,
                'size_label' => '0,5 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2022/09/rilanas500.png',
            ],
            [
                'name' => 'Пепси 1 л',
                'slug' => 'pepsi-1000',
                'short_description' => 'Газирана напитка Pepsi, 1 л.',
                'description' => 'Пепси 1 л.',
                'base_price' => 1.70,
                'size_label' => '1 л',
                'image' => 'https://qbb.bg/wp-content/uploads/2024/05/Pepsi1l.png',
            ],
        ];

        $this->seedSimpleProducts($drinksCategory, $drinks);

        if ($drinksCategory) {
            Product::query()
                ->where('category_id', $drinksCategory->id)
                ->whereNotIn('slug', collect($drinks)->pluck('slug'))
                ->each(fn (Product $product) => $product->delete());
        }

        IngredientCatalogSync::syncProductRecipes();
    }

    protected function seedSimpleProducts(?Category $category, array $products): void
    {
        if (! $category) {
            return;
        }

        foreach ($products as $index => $data) {
            $attributes = [
                'category_id' => $category->id,
                'name' => $data['name'],
                'short_description' => $data['short_description'],
                'description' => $data['description'],
                'base_price' => $data['base_price'],
                'is_active' => true,
                'is_featured' => $data['is_featured'] ?? false,
                'is_promo' => $data['is_promo'] ?? false,
                'is_new' => $data['is_new'] ?? false,
                'is_spicy' => false,
                'sort_order' => $index + 1,
                'seo_title' => $data['name'].' | Allo! Pizza',
                'seo_description' => $data['short_description'],
            ];

            if (array_key_exists('allows_extras', $data)) {
                $attributes['allows_extras'] = $data['allows_extras'];
            }

            if (isset($data['image'])) {
                $attributes['image'] = $data['image'];
            }

            $product = Product::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $attributes
            );

            $product->variants()->updateOrCreate(
                ['name' => 'Стандартен', 'size_label' => $data['size_label'] ?? '1 бр.'],
                [
                    'price' => $data['base_price'],
                    'extra_price_multiplier' => 1.00,
                    'weight' => $data['size_label'] ?? null,
                    'diameter' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );

            $product->ingredients()->sync([]);
        }
    }
}
