<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 860px; line-height: 1.4; }
        nav a { margin-right: 12px; }
        .card { border: 1px solid #ddd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .muted { color: #666; }
    </style>
</head>
<body>
<nav>
    <a href="{{ route('home') }}">Главная</a>
    <a href="{{ route('login') }}">Логин</a>
    <a href="{{ route('dispatcher.dashboard') }}">Dispatcher</a>
    <a href="{{ route('master.dashboard') }}">Master</a>
    <a href="{{ route('requests.create') }}">Создать заявку</a>
</nav>
<hr>
@yield('content')
</body>
</html>
