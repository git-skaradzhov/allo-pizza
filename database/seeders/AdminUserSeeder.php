<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->whereIn('email', ['info@allopizza.net', 'admin@pizzeria.local'])
            ->first();

        if ($admin) {
            $admin->update([
                'name' => 'Администратор',
                'email' => 'info@allopizza.net',
                'password' => 'Allo20Pizza26',
                'role' => UserRole::Administrator,
                'email_verified_at' => now(),
            ]);

            return;
        }

        User::query()->create([
            'name' => 'Администратор',
            'email' => 'info@allopizza.net',
            'password' => 'Allo20Pizza26',
            'role' => UserRole::Administrator,
            'email_verified_at' => now(),
        ]);
    }
}
