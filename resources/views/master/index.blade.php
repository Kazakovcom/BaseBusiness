@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель мастера</h2>
        <p>Пользователь: {{ $currentUser->name }}</p>
        <p>Функциональность рабочего процесса мастера будет добавлена позже.</p>
    </div>
@endsection
