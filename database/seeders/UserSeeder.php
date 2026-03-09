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
            ['name' => 'Дарья Диспетчер', 'role' => UserRole::Dispatcher->value]
        );

        User::query()->updateOrCreate(
            ['email' => 'master1@example.com'],
            ['name' => 'Максим Мастер', 'role' => UserRole::Master->value]
        );

        User::query()->updateOrCreate(
            ['email' => 'master2@example.com'],
            ['name' => 'Елена Мастер', 'role' => UserRole::Master->value]
        );
    }
}
