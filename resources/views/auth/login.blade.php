@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Вход в систему</h2>
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <label for="user_id">Выберите пользователя:</label>
            <select id="user_id" name="user_id" required>
                <option value="">— Выберите —</option>

                <optgroup label="Диспетчер">
                    @foreach($users->where('role', \App\Enums\UserRole::Dispatcher->value) as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </optgroup>

                <optgroup label="Мастера">
                    @foreach($users->where('role', \App\Enums\UserRole::Master->value) as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </optgroup>
            </select>
            <button type="submit">Войти</button>
        </form>
    </div>
@endsection
