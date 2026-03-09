@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>BaseBusiness — каркас тестового проекта</h1>
        <p>Текущий этап включает инфраструктуру Laravel 12, SQLite, сиды, базовую авторизацию по выбору пользователя и заглушки страниц.</p>
        @if($currentUser)
            <p class="muted">Текущий пользователь: {{ $currentUser->name }} ({{ $currentUser->role }})</p>
        @endif
    </div>
@endsection
