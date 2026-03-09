<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'dispatcher@example.com'],
            ['name' => 'Daria Dispatcher', 'role' => UserRole::Dispatcher->value]
        );

        User::query()->updateOrCreate(
            ['email' => 'master1@example.com'],
            ['name' => 'Maksim Master', 'role' => UserRole::Master->value]
        );

        User::query()->updateOrCreate(
            ['email' => 'master2@example.com'],
            ['name' => 'Elena Master', 'role' => UserRole::Master->value]
        );
    }
}
