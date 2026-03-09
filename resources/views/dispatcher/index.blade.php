@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель диспетчера</h2>
        <p>Пользователь: {{ $currentUser->name }}</p>

        @if (session('success'))
            <p style="color: green;">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p style="color: #b91c1c;">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <ul style="color: #b91c1c; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="GET" action="{{ route('dispatcher.dashboard') }}" style="margin-bottom: 1rem;">
            <label for="status">Фильтр по статусу:</label>
            <select name="status" id="status">
                <option value="">Все</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button type="submit">Применить</button>
            @if ($selectedStatus !== '')
                <a href="{{ route('dispatcher.dashboard') }}">Сбросить</a>
            @endif
        </form>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Адрес</th>
                    <th>Проблема</th>
                    <th>Статус</th>
                    <th>Назначенный мастер</th>
                    <th>Создана</th>
                    <th>Обновлена</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $serviceRequest)
                    <tr>
                        <td>{{ $serviceRequest->id }}</td>
                        <td>{{ $serviceRequest->client_name }}</td>
                        <td>{{ $serviceRequest->phone }}</td>
                        <td>{{ $serviceRequest->address }}</td>
                        <td>{{ $serviceRequest->problem_text }}</td>
                        <td>{{ $serviceRequest->status }}</td>
                        <td>{{ $serviceRequest->assignedMaster?->name ?? '—' }}</td>
                        <td>{{ $serviceRequest->created_at }}</td>
                        <td>{{ $serviceRequest->updated_at }}</td>
                        <td>
                            @if ($serviceRequest->status === \App\Enums\RequestStatus::New->value)
                                <form method="POST" action="{{ route('dispatcher.requests.assign', ['serviceRequest' => $serviceRequest, 'status' => $selectedStatus]) }}" style="margin-bottom: 8px;">
                                    @csrf
                                    <label>
                                        <span class="muted">Мастер:</span>
                                        <select name="master_id" required>
                                            <option value="">Выберите мастера</option>
                                            @foreach ($masters as $master)
                                                <option value="{{ $master->id }}">{{ $master->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button type="submit">Назначить</button>
                                </form>
                            @endif

                            @if (in_array($serviceRequest->status, [\App\Enums\RequestStatus::New->value, \App\Enums\RequestStatus::Assigned->value], true))
                                <form method="POST" action="{{ route('dispatcher.requests.cancel', ['serviceRequest' => $serviceRequest, 'status' => $selectedStatus]) }}">
                                    @csrf
                                    <button type="submit">Отменить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">Заявки не найдены.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
