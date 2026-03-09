@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель диспетчера</h2>
        <p>Пользователь: {{ $currentUser->name }}</p>
        <p class="muted">Завершение заявки выполняется в рабочем процессе мастера.</p>

        @if (session('success'))
            <p class="status-success">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="status-error">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <ul class="status-error" style="padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="GET" action="{{ route('dispatcher.dashboard') }}" style="margin-bottom: 1rem; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <label for="status">Фильтр по статусу:</label>
            <select name="status" id="status">
                <option value="">Все</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>
                        {{ \App\Enums\RequestStatus::from($status)->label() }}
                    </option>
                @endforeach
            </select>
            <button type="submit">Применить</button>
            @if ($selectedStatus !== '')
                <a href="{{ route('dispatcher.dashboard') }}">Сбросить</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">ID</th>
                        <th style="min-width: 140px;">Клиент</th>
                        <th style="min-width: 130px;">Телефон</th>
                        <th style="min-width: 180px;">Адрес</th>
                        <th style="min-width: 220px;">Проблема</th>
                        <th style="min-width: 110px;">Статус</th>
                        <th style="min-width: 150px;">Назначенный мастер</th>
                        <th style="min-width: 150px;">Создана</th>
                        <th style="min-width: 150px;">Обновлена</th>
                        <th style="min-width: 180px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $serviceRequest)
                        <tr>
                            <td>{{ $serviceRequest->id }}</td>
                            <td class="text-wrap">{{ $serviceRequest->client_name }}</td>
                            <td>{{ $serviceRequest->phone }}</td>
                            <td class="text-wrap">{{ $serviceRequest->address }}</td>
                            <td class="text-wrap">{{ $serviceRequest->problem_text }}</td>
                            <td>{{ \App\Enums\RequestStatus::tryFrom($serviceRequest->status)?->label() ?? $serviceRequest->status }}</td>
                            <td class="text-wrap">{{ $serviceRequest->assignedMaster?->name ?? '—' }}</td>
                            <td>{{ $serviceRequest->created_at }}</td>
                            <td>{{ $serviceRequest->updated_at }}</td>
                            <td class="actions">
                                @if ($serviceRequest->status === \App\Enums\RequestStatus::New->value)
                                    <form method="POST" action="{{ route('dispatcher.requests.assign', ['serviceRequest' => $serviceRequest, 'status' => $selectedStatus]) }}">
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
    </div>
@endsection
