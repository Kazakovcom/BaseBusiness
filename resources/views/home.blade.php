@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>BaseBusiness — тестовое задание</h1>
        <p>В проекте уже реализованы: создание заявки (`/requests/create`) и панель диспетчера (`/dispatcher`) с назначением мастера и отменой заявки.</p>
        <p>Панель мастера (`/master`) пока не реализована как полноценный рабочий процесс.</p>
        @if($currentUser)
            <p class="muted">
                Текущий пользователь: {{ $currentUser->name }}
                ({{ \App\Enums\UserRole::tryFrom($currentUser->role)?->label() ?? $currentUser->role }})
            </p>
        @endif
    </div>
@endsection
