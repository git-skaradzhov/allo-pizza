<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->localImages() as $slug => $path) {
            DB::table('products')
                ->where('slug', $slug)
                ->where('image', 'like', 'http%')
                ->update(['image' => $path]);
        }
    }

    public function down(): void
    {
        foreach ($this->remoteImages() as $slug => $url) {
            DB::table('products')
                ->where('slug', $slug)
                ->where('image', $this->localImages()[$slug])
                ->update(['image' => $url]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function localImages(): array
    {
        return [
            'pepsi-500' => 'products/pepsi-500-1024.webp',
            'pepsi-zero-500' => 'products/pepsi-zero-500-1024.webp',
            'mirinda-portokal-500' => 'products/mirinda-portokal-500-1024.webp',
            'mirinda-limon-500' => 'products/mirinda-limon-500-1024.webp',
            'mirinda-ananas-500' => 'products/mirinda-ananas-500-1024.webp',
            'evervess-tonik-500' => 'products/evervess-tonik-500-1024.webp',
            'prisun-gorski-plodove-500' => 'products/prisun-gorski-plodove-500-1024.webp',
            'prisun-yabalka-500' => 'products/prisun-yabalka-500-1024.webp',
            'velingrad-gazirana-500' => 'products/velingrad-gazirana-500-1024.webp',
            'rilana-izvorna-500' => 'products/rilana-izvorna-500-1024.webp',
            'pepsi-1000' => 'products/pepsi-1000-1024.webp',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function remoteImages(): array
    {
        return [
            'pepsi-500' => 'https://qbb.bg/wp-content/uploads/2018/05/Pepsi0.5l.png',
            'pepsi-zero-500' => 'https://qbb.bg/wp-content/uploads/2018/05/500.pz_.png',
            'mirinda-portokal-500' => 'https://qbb.bg/wp-content/uploads/2018/06/500mO.png',
            'mirinda-limon-500' => 'https://qbb.bg/wp-content/uploads/2024/05/500ml.png',
            'mirinda-ananas-500' => 'https://qbb.bg/wp-content/uploads/2026/05/Mirinda_05L_Pineapple.png',
            'evervess-tonik-500' => 'https://qbb.bg/wp-content/uploads/2018/05/500evt.png',
            'prisun-gorski-plodove-500' => 'https://qbb.bg/wp-content/uploads/2024/05/500pff.png',
            'prisun-yabalka-500' => 'https://qbb.bg/wp-content/uploads/2024/05/500pa.png',
            'velingrad-gazirana-500' => 'https://www.velingradvoda.bg/assets/upload/files/3d-model-vgcarbonated-05l-bg-bubbles.jpg',
            'rilana-izvorna-500' => 'https://qbb.bg/wp-content/uploads/2022/09/rilanas500.png',
            'pepsi-1000' => 'https://qbb.bg/wp-content/uploads/2024/05/Pepsi1l.png',
        ];
    }
};
