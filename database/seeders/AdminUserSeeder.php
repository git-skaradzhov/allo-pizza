<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@localhost');
        $password = env('ADMIN_PASSWORD');

        $admin = User::query()
            ->whereIn('email', array_filter([$email, 'info@allopizza.net', 'admin@pizzeria.local', 'admin@localhost']))
            ->first();

        if ($admin) {
            $data = [
                'name' => 'Администратор',
                'email' => $email,
                'role' => UserRole::Administrator,
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ];

            if (filled($password)) {
                $data['password'] = $password;
            }

            $admin->update($data);

            return;
        }

        User::query()->create([
            'name' => 'Администратор',
            'email' => $email,
            'password' => filled($password) ? $password : 'password',
            'role' => UserRole::Administrator,
            'email_verified_at' => now(),
        ]);
    }
}
