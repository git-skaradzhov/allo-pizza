<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<array{table: string, columns: list<string>}> */
    private array $textTargets = [
        ['table' => 'categories', 'columns' => ['name', 'description', 'seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description', 'focus_keyword']],
        ['table' => 'products', 'columns' => ['name', 'short_description', 'description', 'image_alt', 'seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description', 'focus_keyword']],
        ['table' => 'product_variants', 'columns' => ['name', 'size_label', 'weight']],
        ['table' => 'ingredients', 'columns' => ['name', 'portion_weight']],
        ['table' => 'lunch_menu_items', 'columns' => ['section', 'name', 'description']],
        ['table' => 'pages', 'columns' => ['title', 'content', 'seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description', 'focus_keyword']],
        ['table' => 'banners', 'columns' => ['title', 'subtitle', 'button_text']],
        ['table' => 'store_settings', 'columns' => ['store_name', 'store_address', 'store_email']],
        ['table' => 'order_item_options', 'columns' => ['name']],
        ['table' => 'cart_items', 'columns' => ['note']],
        ['table' => 'order_items', 'columns' => ['product_name', 'variant_name', 'note']],
        ['table' => 'orders', 'columns' => ['customer_name', 'customer_phone', 'customer_email', 'delivery_address', 'note']],
    ];

    public function up(): void
    {
        foreach ($this->textTargets as $target) {
            if (! $this->tableExists($target['table'])) {
                continue;
            }

            DB::table($target['table'])
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($target) {
                    foreach ($rows as $row) {
                        $updates = [];

                        foreach ($target['columns'] as $column) {
                            if (! property_exists($row, $column)) {
                                continue;
                            }

                            $fixed = $this->fixMojibake($row->{$column});

                            if ($fixed !== null && $fixed !== $row->{$column}) {
                                $updates[$column] = $fixed;
                            }
                        }

                        if ($updates !== []) {
                            DB::table($target['table'])
                                ->where('id', $row->id)
                                ->update($updates);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Encoding repair is not safely reversible.
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function fixMojibake(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || ! $this->looksDoubleEncoded($value)) {
            return is_string($value) ? $value : null;
        }

        $fixed = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);

        if ($fixed === false || $fixed === '' || ! mb_check_encoding($fixed, 'UTF-8')) {
            return $value;
        }

        return $fixed;
    }

    private function looksDoubleEncoded(string $value): bool
    {
        return str_contains($value, 'Ð')
            || str_contains($value, 'Ñ')
            || str_contains($value, 'Ã')
            || str_contains($value, 'â€');
    }
};
