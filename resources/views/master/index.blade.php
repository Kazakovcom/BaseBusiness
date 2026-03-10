@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Панель мастера</h2>
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

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 52px;">ID</th>
                        <th style="width: 140px;">Клиент</th>
                        <th style="width: 120px;">Телефон</th>
                        <th style="width: 180px;">Адрес</th>
                        <th style="width: 240px;">Проблема</th>
                        <th style="width: 95px;">Статус</th>
                        <th style="width: 180px;">Даты</th>
                        <th style="width: 155px;">Действия</th>
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
                            <td>
                                <div><span class="hint">Создана:</span> {{ $serviceRequest->created_at?->format('d.m.Y H:i') }}</div>
                                <div><span class="hint">Обновлена:</span> {{ $serviceRequest->updated_at?->format('d.m.Y H:i') }}</div>
                            </td>
                            <td class="actions">
                                @if ($serviceRequest->status === \App\Enums\RequestStatus::Assigned->value)
                                    <form method="POST" action="{{ route('master.requests.take', ['serviceRequest' => $serviceRequest]) }}">
                                        @csrf
                                        <button type="submit">Взять в работу</button>
                                    </form>
                                @elseif ($serviceRequest->status === \App\Enums\RequestStatus::InProgress->value)
                                    <form method="POST" action="{{ route('master.requests.complete', ['serviceRequest' => $serviceRequest]) }}">
                                        @csrf
                                        <button type="submit">Завершить</button>
                                    </form>
                                @else
                                    <span class="hint">Действие для этого статуса недоступно.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">У вас пока нет назначенных заявок.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
