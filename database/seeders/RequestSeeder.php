<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $firstMaster = User::query()->where('email', 'master1@example.com')->first();
        $secondMaster = User::query()->where('email', 'master2@example.com')->first();

        ServiceRequest::query()->updateOrCreate(
            [
                'client_name' => 'Иван Петров',
                'phone' => '+7 900 111-22-33',
                'address' => 'Москва, ул. Ленина, д. 1',
            ],
            [
                'problem_text' => 'Не работает кондиционер',
                'status' => RequestStatus::New->value,
                'assigned_to' => null,
            ]
        );

        ServiceRequest::query()->updateOrCreate(
            [
                'client_name' => 'Ольга Смирнова',
                'phone' => '+7 900 222-33-44',
                'address' => 'Москва, ул. Пушкина, д. 10',
            ],
            [
                'problem_text' => 'Протечка под раковиной',
                'status' => RequestStatus::Assigned->value,
                'assigned_to' => $firstMaster?->id,
            ]
        );

        ServiceRequest::query()->updateOrCreate(
            [
                'client_name' => 'Сергей Иванов',
                'phone' => '+7 900 333-44-55',
                'address' => 'Москва, ул. Тверская, д. 7',
            ],
            [
                'problem_text' => 'Не включается стиральная машина',
                'status' => RequestStatus::InProgress->value,
                'assigned_to' => $secondMaster?->id,
            ]
        );

        ServiceRequest::query()->updateOrCreate(
            [
                'client_name' => 'Елена Кузнецова',
                'phone' => '+7 900 444-55-66',
                'address' => 'Москва, ул. Арбат, д. 12',
            ],
            [
                'problem_text' => 'Замена розетки выполнена',
                'status' => RequestStatus::Done->value,
                'assigned_to' => $firstMaster?->id,
            ]
        );

        ServiceRequest::query()->updateOrCreate(
            [
                'client_name' => 'Дмитрий Соколов',
                'phone' => '+7 900 555-66-77',
                'address' => 'Москва, ул. Мира, д. 21',
            ],
            [
                'problem_text' => 'Клиент отменил выезд мастера',
                'status' => RequestStatus::Canceled->value,
                'assigned_to' => null,
            ]
        );
    }
}
