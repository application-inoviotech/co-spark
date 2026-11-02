<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Create (or promote) an admin account: php artisan cospark:admin office@example.com */
class CreateAdmin extends Command
{
    protected $signature = 'cospark:admin {email} {--first=COSPARK} {--last=Admin}';

    protected $description = 'Create an admin account (or give an existing user admin access)';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $password = $this->secret('Password (leave empty to generate one)') ?: Str::password(16);

        $user = User::firstOrNew(['email' => $email]);
        $user->fill(['first_name' => $user->first_name ?? $this->option('first'), 'last_name' => $user->last_name ?? $this->option('last'), 'role' => User::ROLE_ADMIN, 'status' => 'active', 'password' => $password]);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info("Admin ready: {$email}");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
