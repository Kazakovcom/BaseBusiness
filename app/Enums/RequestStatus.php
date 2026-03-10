<?php

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Canceled = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Assigned => 'Назначена',
            self::InProgress => 'В работе',
            self::Done => 'Выполнена',
            self::Canceled => 'Отменена',
        };
    }
}
