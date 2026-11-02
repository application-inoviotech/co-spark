<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Local/staging admin. In production create one with `php artisan cospark:admin you@example.com`. */
class AdminSeeder extends Seeder
{
    public const EMAIL = 'admin@cospark.test';

    public const PASSWORD = 'AdminCospark2026';

    public function run(): void
    {
        User::firstOrCreate(['email' => self::EMAIL], [
            'first_name' => 'COSPARK',
            'last_name' => 'Admin',
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
            'password' => self::PASSWORD,
        ])->forceFill(['email_verified_at' => now()])->save();
    }
}
