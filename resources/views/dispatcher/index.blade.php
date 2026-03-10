@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель диспетчера</h2>
        <p>Пользователь: {{ $currentUser->name }}</p>

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

        <form method="GET" action="{{ route('dispatcher.dashboard') }}" style="margin-bottom: 0.75rem; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
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
                        <th style="width: 52px;">ID</th>
                        <th style="width: 120px;">Клиент</th>
                        <th style="width: 120px;">Телефон</th>
                        <th style="width: 170px;">Адрес</th>
                        <th style="width: 210px;">Проблема</th>
                        <th style="width: 95px;">Статус</th>
                        <th style="width: 130px;">Мастер</th>
                        <th style="width: 180px;">Даты</th>
                        <th style="width: 165px;">Действия</th>
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
                            <td>
                                <div><span class="hint">Создана:</span> {{ $serviceRequest->created_at?->format('d.m.Y H:i') }}</div>
                                <div><span class="hint">Обновлена:</span> {{ $serviceRequest->updated_at?->format('d.m.Y H:i') }}</div>
                            </td>
                            <td class="actions">
                                @if ($serviceRequest->status === \App\Enums\RequestStatus::New->value)
                                    <form method="POST" action="{{ route('dispatcher.requests.assign', ['serviceRequest' => $serviceRequest, 'status' => $selectedStatus]) }}">
                                        @csrf
                                        <select name="master_id" required style="max-width: 140px;">
                                            <option value="">Мастер</option>
                                            @foreach ($masters as $master)
                                                <option value="{{ $master->id }}">{{ $master->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Назначить</button>
                                    </form>
                                @endif

                                @if (in_array($serviceRequest->status, [\App\Enums\RequestStatus::New->value, \App\Enums\RequestStatus::Assigned->value], true))
                                    <form method="POST" action="{{ route('dispatcher.requests.cancel', ['serviceRequest' => $serviceRequest, 'status' => $selectedStatus]) }}">
                                        @csrf
                                        <button type="submit">Отменить</button>
                                    </form>
                                @elseif (in_array($serviceRequest->status, [\App\Enums\RequestStatus::InProgress->value, \App\Enums\RequestStatus::Done->value], true))
                                    <span class="hint">Действия доступны в рабочем процессе мастера.</span>
                                @else
                                    <span class="hint">Нет доступных действий.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Заявки не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
