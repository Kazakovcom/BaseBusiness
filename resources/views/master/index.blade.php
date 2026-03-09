@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель мастера</h2>
        <p>Пользователь: {{ $currentUser->name }}</p>
        <p>Эта страница подготовлена как заглушка для следующего этапа.</p>
    </div>
@endsection
