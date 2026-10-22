<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Konto deweloperskie dla panelu /admin. Dane bierze z .env (ADMIN_EMAIL, ADMIN_PASSWORD) i nigdy
 * nie działa w produkcji. W produkcji konto zakłada się poleceniem: php artisan make:filament-user
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $admin = config('clinic.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            return;
        }

        User::updateOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => Hash::make($admin['password'])],
        );
    }
}
