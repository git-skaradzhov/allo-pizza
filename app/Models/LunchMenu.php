<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class LunchMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'message',
        'start_time',
        'end_time',
        'days_of_week',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(LunchMenuItem::class, 'lunch_menu_lunch_menu_item')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** @deprecated Use items() instead */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'lunch_menu_product');
    }

    public const TEASER_SECTION_GROUPS = [
        ['Супи', 'Салати'],
        ['Пици', 'Пърленки'],
        ['Десерти'],
    ];

    public function itemsGroupedByMealOrder(): Collection
    {
        $grouped = $this->items
            ->where('is_active', true)
            ->sortBy(fn (LunchMenuItem $item) => $item->mealSortKey())
            ->groupBy('section');

        return collect(LunchMenuItem::MEAL_SECTION_ORDER)
            ->filter(fn (string $section) => $grouped->has($section))
            ->mapWithKeys(fn (string $section) => [$section => $grouped->get($section)]);
    }

    /**
     * @return array{0: Collection<string, Collection<int, LunchMenuItem>>, 1: Collection<string, Collection<int, LunchMenuItem>>}
     */
    public function mealOrderColumns(): array
    {
        $groups = $this->itemsGroupedByMealOrder();
        $splitAt = (int) ceil($groups->count() / 2);

        return [
            $groups->take($splitAt),
            $groups->skip($splitAt),
        ];
    }

    public function teaserItems(): Collection
    {
        $items = $this->items
            ->where('is_active', true)
            ->sortBy(fn (LunchMenuItem $item) => $item->mealSortKey());

        return collect(self::TEASER_SECTION_GROUPS)
            ->map(fn (array $sections) => $items->first(
                fn (LunchMenuItem $item) => in_array($item->section, $sections, true)
            ))
            ->filter()
            ->values();
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        $dayOfWeek = $now->dayOfWeekIso;

        if (! in_array($dayOfWeek, $this->days_of_week ?? [])) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }

    public static function isPublished(): bool
    {
        return static::query()->where('is_active', true)->exists();
    }
}
