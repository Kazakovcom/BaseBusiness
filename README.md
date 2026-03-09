# BaseBusiness — тестовое задание (этап 2: панель диспетчера)

## Что реализовано
- Минимальный Laravel-совместимый baseline-каркас с точкой входа `public/index.php`.
- Docker-конфигурация для локального запуска через `docker compose`.
- SQLite как БД по умолчанию.
- Реальные миграции:
  - `users`
  - `requests`
- Реальные сидеры:
  - `DatabaseSeeder`
  - `UserSeeder` (1 dispatcher, 2 masters)
  - `RequestSeeder` (несколько заявок с разными статусами)
- Упрощённая авторизация по выбору пользователя (`/login`) с хранением `auth_user_id` в сессии.
- Экран создания заявки:
  - `GET /requests/create`
  - `POST /requests` с валидацией `client_name`, `phone`, `address`, `problem_text`
  - новая заявка сохраняется со статусом `new`
- Панель диспетчера `GET /dispatcher`:
  - список заявок с полями `id`, `client_name`, `phone`, `address`, `problem_text`, `status`, назначенный мастер, `created_at`, `updated_at`;
  - фильтр по статусу через query-параметр `status`;
  - назначение мастера на заявку;
  - отмена заявки;
  - flash-сообщения об успехе/ошибке операций.

## Как пользоваться панелью диспетчера
1. Войти через `/login` пользователем с ролью dispatcher.
2. Открыть `/dispatcher`.
3. При необходимости отфильтровать список заявок по статусу (`new`, `assigned`, `in_progress`, `done`, `canceled`).
4. Для заявки в статусе `new`:
   - выбрать мастера из выпадающего списка;
   - нажать «Назначить».
5. Для заявки в статусе `new` или `assigned`:
   - нажать «Отменить».

## Бизнес-правила, уже реализованные
- Назначать мастера можно только заявке в статусе `new`.
- После назначения:
  - `assigned_to` = выбранный мастер;
  - `status` = `assigned`.
- Отменять можно только заявки в статусах `new` и `assigned`.
- После отмены:
  - `status` = `canceled`;
  - `assigned_to` очищается в `null`.
- Если операция недопустима для текущего статуса, в интерфейсе показывается понятная ошибка.


## Тестовые пользователи
- Dispatcher: `dispatcher@example.com`
- Master: `master1@example.com`
- Master: `master2@example.com`

Вход выполняется через страницу `/login` выбором пользователя из списка:
- для роли диспетчера используйте `dispatcher@example.com`;
- для роли мастера используйте `master1@example.com` или `master2@example.com`.

## Автотесты
- `tests/Feature/HomePageTest.php` — доступность главной страницы.
- `tests/Feature/ServiceRequestCreationTest.php` — создание заявки со статусом `new`.
- `tests/Feature/DispatcherPanelTest.php`:
  - отображение списка заявок и фильтрация по статусу;
  - назначение мастера для заявки в статусе `new`;
  - отмена заявок в статусах `new` и `assigned`;
  - негативный сценарий недопустимых переходов статусов для assign/cancel.

## Что ещё не реализовано
- Панель мастера (workflow мастера).
- Защита race condition в бизнес-операциях.

## Как запустить локально
```bash
docker compose up --build
```

После старта контейнер:
1. создаёт `.env` из `.env.example`, если нужно;
2. гарантирует наличие `database/database.sqlite`;
3. выполняет `composer install`, если отсутствует `vendor`;
4. выполняет `php artisan key:generate` только если `APP_KEY` отсутствует или пустой;
5. выполняет `php artisan migrate --seed`;
6. запускает сервер на `http://localhost:8000`.
