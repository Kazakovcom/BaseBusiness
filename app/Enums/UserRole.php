<?php

namespace App\Enums;

enum UserRole: string
{
    case Dispatcher = 'dispatcher';
    case Master = 'master';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Dispatcher => 'Диспетчер',
            self::Master => 'Мастер',
        };
    }
}
