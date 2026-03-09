@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>BaseBusiness — тестовое задание</h1>
        <p>В проекте уже реализованы: создание заявки (`/requests/create`) и панель диспетчера (`/dispatcher`) с операциями назначения и отмены.</p>
        <p>Панель мастера (`/master`) пока не реализована как полноценный workflow.</p>
        @if($currentUser)
            <p class="muted">Текущий пользователь: {{ $currentUser->name }} ({{ $currentUser->role }})</p>
        @endif
    </div>
@endsection
