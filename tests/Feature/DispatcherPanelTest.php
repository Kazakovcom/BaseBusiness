<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatcherPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_sees_requests_and_can_filter_by_status(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Диспетчер Тест',
            'email' => 'dispatcher@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        ServiceRequest::query()->create([
            'client_name' => 'Новая заявка',
            'phone' => '+7 111 111-11-11',
            'address' => 'Адрес 1',
            'problem_text' => 'Проблема 1',
            'status' => RequestStatus::New->value,
        ]);

        ServiceRequest::query()->create([
            'client_name' => 'Заявка в процессе',
            'phone' => '+7 222 222-22-22',
            'address' => 'Адрес 2',
            'problem_text' => 'Проблема 2',
            'status' => RequestStatus::InProgress->value,
        ]);

        $allResponse = $this->withSession(['auth_user_id' => $dispatcher->id])->get('/dispatcher');
        $allResponse->assertOk();
        $allResponse->assertSee('Новая заявка');
        $allResponse->assertSee('Заявка в процессе');
        $allResponse->assertSee('+7 111 111-11-11');
        $allResponse->assertSee('+7 222 222-22-22');

        $filteredResponse = $this->withSession(['auth_user_id' => $dispatcher->id])->get('/dispatcher?status='.RequestStatus::New->value);
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Новая заявка');
        $filteredResponse->assertSee('+7 111 111-11-11');
        $filteredResponse->assertSee('Адрес 1');
        $filteredResponse->assertSee('Проблема 1');
        $filteredResponse->assertDontSee('Заявка в процессе');
        $filteredResponse->assertDontSee('+7 222 222-22-22');
        $filteredResponse->assertDontSee('Адрес 2');
        $filteredResponse->assertDontSee('Проблема 2');
    }

    public function test_dispatcher_can_assign_master_for_new_request(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Dispatcher',
            'email' => 'dispatcher.assign@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        $master = User::query()->create([
            'name' => 'Master',
            'email' => 'master.assign@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент',
            'phone' => '+7 333 333-33-33',
            'address' => 'Адрес 3',
            'problem_text' => 'Проблема 3',
            'status' => RequestStatus::New->value,
        ]);

        $response = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.assign', ['serviceRequest' => $serviceRequest]), [
                'master_id' => $master->id,
            ]);

        $response->assertRedirect(route('dispatcher.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_dispatcher_can_cancel_new_or_assigned_request(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Dispatcher',
            'email' => 'dispatcher.cancel@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        $newRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент 1',
            'phone' => '+7 444 444-44-44',
            'address' => 'Адрес 4',
            'problem_text' => 'Проблема 4',
            'status' => RequestStatus::New->value,
        ]);

        $assignedMaster = User::query()->create([
            'name' => 'Assigned Master',
            'email' => 'assigned.master.cancel@test.local',
            'role' => UserRole::Master->value,
        ]);

        $assignedRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент 2',
            'phone' => '+7 555 555-55-55',
            'address' => 'Адрес 5',
            'problem_text' => 'Проблема 5',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $assignedMaster->id,
        ]);

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.cancel', ['serviceRequest' => $newRequest]))
            ->assertRedirect(route('dispatcher.dashboard'));

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.cancel', ['serviceRequest' => $assignedRequest]))
            ->assertRedirect(route('dispatcher.dashboard'));

        $this->assertDatabaseHas('requests', [
            'id' => $newRequest->id,
            'status' => RequestStatus::Canceled->value,
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $assignedRequest->id,
            'status' => RequestStatus::Canceled->value,
            'assigned_to' => null,
        ]);
    }

    public function test_dispatcher_cannot_assign_or_cancel_request_with_invalid_status_transition(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Dispatcher',
            'email' => 'dispatcher.negative@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        $master = User::query()->create([
            'name' => 'Master',
            'email' => 'master.negative@test.local',
            'role' => UserRole::Master->value,
        ]);

        $doneRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент 3',
            'phone' => '+7 666 666-66-66',
            'address' => 'Адрес 6',
            'problem_text' => 'Проблема 6',
            'status' => RequestStatus::Done->value,
        ]);

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.assign', ['serviceRequest' => $doneRequest]), [
                'master_id' => $master->id,
            ])
            ->assertRedirect(route('dispatcher.dashboard'))
            ->assertSessionHas('error');

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.cancel', ['serviceRequest' => $doneRequest]))
            ->assertRedirect(route('dispatcher.dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('requests', [
            'id' => $doneRequest->id,
            'status' => RequestStatus::Done->value,
            'assigned_to' => null,
        ]);
    }
}
