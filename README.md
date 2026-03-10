# BaseBusiness — тестовое задание (этап 3: панель мастера и safe take)

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
  - `RequestSeeder` (демо-заявки со статусами `new`, `assigned`, `in_progress`, `done`, `canceled`)
- Упрощённая авторизация по выбору пользователя (`/login`) с хранением `auth_user_id` в сессии.
- Экран создания заявки:
  - `GET /requests/create`
  - `POST /requests` с валидацией `client_name`, `phone`, `address`, `problem_text`
  - новая заявка сохраняется со статусом `new`
- Панель диспетчера `GET /dispatcher`:
  - список заявок;
  - фильтр по статусу;
  - назначение мастера;
  - отмена заявки;
  - flash-сообщения об успехе/ошибке операций.
- Панель мастера `GET /master`:
  - мастер видит только заявки, где `assigned_to` совпадает с его `id`;
  - в таблице выводятся `id`, `client_name`, `phone`, `address`, `problem_text`, `status`, `created_at`, `updated_at`;
  - для заявки в статусе `assigned` доступно действие «Взять в работу»;
  - для заявки в статусе `in_progress` доступно действие «Завершить»;
  - показываются flash-сообщения об успехе и ошибках.

## Бизнес-правила
- Диспетчер:
  - может назначать мастера только заявке в статусе `new`;
  - после назначения заявка получает `status = assigned`;
  - может отменять только заявки в статусах `new` и `assigned`;
  - после отмены `assigned_to` очищается, `status = canceled`.
- Мастер:
  - видит только свои заявки;
  - может взять в работу только свою заявку в статусе `assigned`;
  - может завершить только свою заявку в статусе `in_progress`;
  - не может переводить чужие заявки или заявки в неподходящем статусе.

## Защита от race condition
- Операция «Взять в работу» реализована на уровне кода через атомарный условный `update`.
- Обновление выполняется только если одновременно выполняются условия:
  - `id` заявки совпадает;
  - `assigned_to` совпадает с текущим мастером;
  - текущий `status = assigned`.
- Поэтому при двух конкурентных попытках только один запрос меняет статус `assigned -> in_progress`.
- Повторный или проигравший конкурентный запрос получает контролируемый отказ, а данные заявки не повреждаются.

## Маршруты текущего этапа
- `GET /dispatcher`
- `POST /dispatcher/requests/{serviceRequest}/assign`
- `POST /dispatcher/requests/{serviceRequest}/cancel`
- `GET /master`
- `POST /master/requests/{serviceRequest}/take`
- `POST /master/requests/{serviceRequest}/complete`
- `GET /requests/create`
- `POST /requests`

## Тестовые пользователи
- Dispatcher: `dispatcher@example.com`
- Master: `master1@example.com`
- Master: `master2@example.com`

Вход выполняется через страницу `/login` выбором пользователя из списка.

## Автотесты в кодовой базе
- `tests/Feature/HomePageTest.php` — доступность главной страницы.
- `tests/Feature/ServiceRequestCreationTest.php` — создание заявки со статусом `new`.
- `tests/Feature/DispatcherPanelTest.php`:
  - список заявок и фильтр по статусу;
  - назначение мастера;
  - отмена заявки;
  - негативные сценарии диспетчера.
- `tests/Feature/MasterPanelTest.php`:
  - мастер видит только свои заявки;
  - перевод своей заявки `assigned -> in_progress`;
  - перевод своей заявки `in_progress -> done`;
  - отказ для чужой заявки и недопустимого статуса;
  - защита мастерских маршрутов ролью.
- `tests/Feature/MasterRequestWorkflowServiceTest.php`:
  - первый вызов `take` успешен;
  - повторный вызов получает контролируемый отказ и не меняет данные повторно.

## Что остаётся на финальный этап
- Финальная доводка README по сдаче.
- Финальная инструкция по ручной проверке конкурентного сценария.
- Отдельный `race_test.sh`, если он потребуется для финальной сдачи.
- Финальная упаковка репозитория под передачу.

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
