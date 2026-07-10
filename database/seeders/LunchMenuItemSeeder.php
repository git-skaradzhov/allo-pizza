<?php

namespace Database\Seeders;

use App\Models\LunchMenuItem;
use App\Support\Money;
use Illuminate\Database\Seeder;

class LunchMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['section' => 'Супи', 'name' => 'Таратор', 'description' => '300 гр. – кисело мляко, краставици, орехи, копър', 'price' => 3.13, 'sort_order' => 1],
            ['section' => 'Супи', 'name' => 'Пилешка супа', 'description' => '300 гр. – пилешко месо, зеленчуци, фиде', 'price' => 4.30, 'sort_order' => 2],
            ['section' => 'Супи', 'name' => 'Крем супа от червена леща', 'description' => '300 гр. – леща, моркови, подправки', 'price' => 3.21, 'sort_order' => 3],
            ['section' => 'Салати', 'name' => 'Шопска салата', 'description' => '250 гр. – домати, краставици, чушки, лук, сирене', 'price' => 5.20, 'sort_order' => 1, 'is_hit' => true],
            ['section' => 'Салати', 'name' => 'Салата Столична', 'description' => '200 гр. – картофи, моркови, кисели краставички', 'price' => 3.50, 'sort_order' => 2],
            ['section' => 'Салати', 'name' => 'Цезар с пиле', 'description' => '230 гр. – айсберг, пилешко филе, крутони, пармезан', 'price' => 6.90, 'sort_order' => 3, 'is_new' => true],
            ['section' => 'Салати', 'name' => 'Гръцка салата', 'description' => '230 гр. – домати, краставици, маслини, сирене', 'price' => 5.28, 'sort_order' => 4],
            ['section' => 'Пици', 'name' => 'Маргарита 23 см', 'description' => 'Моцарела, доматен сос, босилек', 'price' => 12.90, 'sort_order' => 1, 'is_hit' => true],
            ['section' => 'Пици', 'name' => 'Пеперони 23 см', 'description' => 'Пикантни салами пеперони, моцарела', 'price' => 14.90, 'sort_order' => 2, 'is_spicy' => true],
            ['section' => 'Пици', 'name' => 'Капричоза 23 см', 'description' => 'Шунка, гъби, маслини, моцарела', 'price' => 15.90, 'sort_order' => 3],
            ['section' => 'Пици', 'name' => 'Вегетариана 23 см', 'description' => 'Чушки, гъби, лук, маслини', 'price' => 13.90, 'sort_order' => 4, 'is_new' => true],
            ['section' => 'Пърленки', 'name' => 'Пърленка с масло', 'description' => 'Топла пърленка с масло и шарена сол', 'price' => 3.90, 'sort_order' => 1],
            ['section' => 'Пърленки', 'name' => 'Пърленка с кашкавал', 'description' => 'Пухкава пърленка с разтопен кашкавал', 'price' => 4.90, 'sort_order' => 2],
            ['section' => 'Пърленки', 'name' => 'Чеснова пърленка', 'description' => 'Пърленка с чесново масло и подправки', 'price' => 4.50, 'sort_order' => 3],
            ['section' => 'Пърленки', 'name' => 'Пърленка комбинирана', 'description' => 'Кашкавал, сирене и чесново масло', 'price' => 5.90, 'sort_order' => 4, 'is_hit' => true],
            ['section' => 'Напитки', 'name' => 'Пепси 0,5 л', 'description' => 'Газирана напитка Pepsi, 0,5 л', 'price' => 2.54, 'sort_order' => 1],
            ['section' => 'Напитки', 'name' => 'Пепси Zero 0,5 л', 'description' => 'Газирана напитка Pepsi Zero, 0,5 л', 'price' => 2.54, 'sort_order' => 2],
            ['section' => 'Напитки', 'name' => 'Mirinda портокал 0,5 л', 'description' => 'Mirinda с вкус на портокал, 0,5 л', 'price' => 2.54, 'sort_order' => 3],
            ['section' => 'Напитки', 'name' => 'Mirinda лимон 0,5 л', 'description' => 'Mirinda с вкус на лимон, 0,5 л', 'price' => 2.54, 'sort_order' => 4],
            ['section' => 'Напитки', 'name' => 'Mirinda ананас 0,5 л', 'description' => 'Mirinda с вкус на ананас, 0,5 л', 'price' => 2.54, 'sort_order' => 5],
            ['section' => 'Напитки', 'name' => 'Evervess тоник 0,5 л', 'description' => 'Газиран тоник Evervess, 0,5 л', 'price' => 2.54, 'sort_order' => 6],
            ['section' => 'Напитки', 'name' => 'Prisun горски плодове 0,5 л', 'description' => 'Prisun с вкус на горски плодове, 0,5 л', 'price' => 2.54, 'sort_order' => 7],
            ['section' => 'Напитки', 'name' => 'Prisun ябълка 0,5 л', 'description' => 'Prisun с вкус на ябълка, 0,5 л', 'price' => 2.54, 'sort_order' => 8],
            ['section' => 'Напитки', 'name' => 'Газирана вода Велинград 0,5 л', 'description' => 'Газирана минерална вода Велинград, 0,5 л', 'price' => 1.96, 'sort_order' => 9],
            ['section' => 'Напитки', 'name' => 'Изворна вода Rilana 0,5 л', 'description' => 'Изворна вода Rilana, 0,5 л', 'price' => 1.56, 'sort_order' => 10],
            ['section' => 'Напитки', 'name' => 'Пепси 1 л', 'description' => 'Газирана напитка Pepsi, 1 л', 'price' => 3.32, 'sort_order' => 11],
            ['section' => 'Десерти', 'name' => 'Мляко с ориз', 'description' => '200 гр. – домашен десерт с канела', 'price' => 2.89, 'sort_order' => 1],
            ['section' => 'Десерти', 'name' => 'Домашен чийзкейк', 'description' => 'Кремообразен десерт с бисквитена основа', 'price' => 5.90, 'sort_order' => 2, 'is_new' => true],
            ['section' => 'Десерти', 'name' => 'Палачинка с шоколад', 'description' => 'Топла палачинка с шоколадов крем', 'price' => 4.90, 'sort_order' => 3],
        ];

        foreach ($items as $item) {
            LunchMenuItem::query()->updateOrCreate(
                ['section' => $item['section'], 'name' => $item['name']],
                array_merge($item, [
                    'price' => Money::fromBgn((float) $item['price']),
                    'is_active' => true,
                    'is_spicy' => $item['is_spicy'] ?? false,
                    'is_hit' => $item['is_hit'] ?? false,
                    'is_new' => $item['is_new'] ?? false,
                ])
            );
        }

        $drinkNames = collect($items)
            ->where('section', 'Напитки')
            ->pluck('name')
            ->all();

        LunchMenuItem::query()
            ->where('section', 'Напитки')
            ->whereNotIn('name', $drinkNames)
            ->delete();
    }
}
