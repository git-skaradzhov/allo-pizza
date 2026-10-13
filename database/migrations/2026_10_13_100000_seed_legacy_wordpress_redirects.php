<?php

use App\Models\Redirect;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Old WordPress URLs still listed as 404 in Search Console.
     *
     * @var array<string, string>
     */
    private array $redirects = [
        '/home' => '/',
        '/shop' => '/menu',
        '/events/happy-solen-party' => '/',
        '/get-the-sensation' => '/',
        '/tag/tasty' => '/menu',
        '/best-burger-ideas' => '/menu',
        '/category/seasonal' => '/menu',
        '/product/chia-berry-smoothie' => '/category/drinks',
        '/product/keto-meatloaf' => '/menu',
        '/product/half-half-pizza' => '/category/pizza',
        '/product/double-steakhous' => '/menu',
        '/product-category/sides' => '/category/sandvici-i-pierlenki',
    ];

    public function up(): void
    {
        foreach ($this->redirects as $from => $to) {
            Redirect::query()->updateOrCreate(
                ['from_path' => $from],
                [
                    'to_path' => $to,
                    'status_code' => 301,
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        Redirect::query()
            ->whereIn('from_path', array_keys($this->redirects))
            ->delete();
    }
};
