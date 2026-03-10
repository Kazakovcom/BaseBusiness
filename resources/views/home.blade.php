@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>BaseBusiness — тестовое задание</h1>
        <p>В проекте уже реализованы: создание заявки (`/requests/create`), панель диспетчера (`/dispatcher`) и панель мастера (`/master`).</p>
        <p>Мастер может брать назначенные заявки в работу и завершать свои заявки в статусе «В работе».</p>
        @if($currentUser)
            <p class="muted">
                Текущий пользователь: {{ $currentUser->name }}
                ({{ \App\Enums\UserRole::tryFrom($currentUser->role)?->label() ?? $currentUser->role }})
            </p>
        @endif
    </div>
@endsection
