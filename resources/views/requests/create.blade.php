@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Создание заявки</h2>

        @if (session('status'))
            <p class="status-success">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('requests.store') }}">
            @csrf
            <div>
                <label>Имя клиента</label>
                <input name="client_name" value="{{ old('client_name') }}" required>
            </div>
            <div>
                <label>Телефон</label>
                <input name="phone" value="{{ old('phone') }}" required>
            </div>
            <div>
                <label>Адрес</label>
                <input name="address" value="{{ old('address') }}" required>
            </div>
            <div>
                <label>Описание проблемы</label>
                <textarea name="problem_text" required>{{ old('problem_text') }}</textarea>
            </div>
            <button type="submit">Создать заявку</button>
        </form>
    </div>
@endsection
