<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 1000px; line-height: 1.4; padding: 0 1rem; }
        nav a { margin-right: 12px; }
        .card { border: 1px solid #ddd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .muted { color: #666; }
        .status-success { color: #166534; }
        .status-error { color: #b91c1c; }
        .table-wrap { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; min-width: 920px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .text-wrap { word-break: break-word; }
        .actions form { margin-bottom: 8px; }
        .actions form:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
<nav>
    <a href="{{ route('home') }}">Главная</a>
    <a href="{{ route('login') }}">Вход</a>
    <a href="{{ route('dispatcher.dashboard') }}">Панель диспетчера</a>
    <a href="{{ route('master.dashboard') }}">Панель мастера</a>
    <a href="{{ route('requests.create') }}">Создать заявку</a>
</nav>
<hr>
@yield('content')
</body>
</html>
