<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Arsus',
            'email'    => 'info@arsus.nl',
            'password' => bcrypt('ario@29'),
            'role'     => UserRole::Admin,
        ]);

        User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@example.com',
            'password' => bcrypt('test12345'),
            'role'     => UserRole::Admin,
        ]);
    }
}
