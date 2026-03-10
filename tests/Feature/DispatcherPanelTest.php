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

        $newRequest = ServiceRequest::query()->create([
            'client_name' => 'Уникальная новая заявка',
            'phone' => '+7 911 111-11-11',
            'address' => 'Адрес NEW 1',
            'problem_text' => 'Проблема NEW 1',
            'status' => RequestStatus::New->value,
        ]);

        $assignedRequest = ServiceRequest::query()->create([
            'client_name' => 'Уникальная assigned заявка',
            'phone' => '+7 922 222-22-22',
            'address' => 'Адрес ASSIGNED 2',
            'problem_text' => 'Проблема ASSIGNED 2',
            'status' => RequestStatus::Assigned->value,
        ]);

        $inProgressRequest = ServiceRequest::query()->create([
            'client_name' => 'Уникальная in_progress заявка',
            'phone' => '+7 933 333-33-33',
            'address' => 'Адрес IN_PROGRESS 3',
            'problem_text' => 'Проблема IN_PROGRESS 3',
            'status' => RequestStatus::InProgress->value,
        ]);

        $allResponse = $this->withSession(['auth_user_id' => $dispatcher->id])->get('/dispatcher');
        $allResponse->assertOk();
        $allResponse->assertSee($newRequest->client_name);
        $allResponse->assertSee($assignedRequest->client_name);
        $allResponse->assertSee($inProgressRequest->client_name);

        $newFilteredResponse = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->get('/dispatcher?status='.RequestStatus::New->value);

        $newFilteredResponse->assertOk();
        $newFilteredResponse->assertSee($newRequest->client_name);
        $newFilteredResponse->assertSee($newRequest->phone);
        $newFilteredResponse->assertSee($newRequest->address);
        $newFilteredResponse->assertSee($newRequest->problem_text);
        $newFilteredResponse->assertDontSee($assignedRequest->client_name);
        $newFilteredResponse->assertDontSee($inProgressRequest->client_name);

        $newFilteredResponse->assertSee(
            route('dispatcher.requests.assign', ['serviceRequest' => $newRequest, 'status' => RequestStatus::New->value]),
            false
        );
        $newFilteredResponse->assertSee(
            route('dispatcher.requests.cancel', ['serviceRequest' => $newRequest, 'status' => RequestStatus::New->value]),
            false
        );

        $assignedFilteredResponse = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->get('/dispatcher?status='.RequestStatus::Assigned->value);

        $assignedFilteredResponse->assertOk();
        $assignedFilteredResponse->assertSee($assignedRequest->client_name);
        $assignedFilteredResponse->assertDontSee($newRequest->client_name);
        $assignedFilteredResponse->assertDontSee($inProgressRequest->client_name);
        $assignedFilteredResponse->assertSee(
            route('dispatcher.requests.cancel', ['serviceRequest' => $assignedRequest, 'status' => RequestStatus::Assigned->value]),
            false
        );
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

    public function test_dispatcher_actions_keep_raw_filter_after_post_and_show_consistent_results(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Dispatcher',
            'email' => 'dispatcher.filtered-post@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        $master = User::query()->create([
            'name' => 'Master',
            'email' => 'master.filtered-post@test.local',
            'role' => UserRole::Master->value,
        ]);

        $newRequest = ServiceRequest::query()->create([
            'client_name' => 'Уникальная NEW для assign',
            'phone' => '+7 944 444-44-44',
            'address' => 'Адрес NEW POST',
            'problem_text' => 'Проблема NEW POST',
            'status' => RequestStatus::New->value,
        ]);

        $assignedRequest = ServiceRequest::query()->create([
            'client_name' => 'Уникальная ASSIGNED для cancel',
            'phone' => '+7 955 555-55-55',
            'address' => 'Адрес ASSIGNED POST',
            'problem_text' => 'Проблема ASSIGNED POST',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $assignResponse = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(
                route('dispatcher.requests.assign', ['serviceRequest' => $newRequest, 'status' => RequestStatus::New->value]),
                ['master_id' => $master->id]
            );

        $assignResponse->assertRedirect(route('dispatcher.dashboard', ['status' => RequestStatus::New->value]));

        $newFilteredAfterAssign = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->get(route('dispatcher.dashboard', ['status' => RequestStatus::New->value]));

        $newFilteredAfterAssign->assertOk();
        $newFilteredAfterAssign->assertDontSee($newRequest->client_name);

        $cancelResponse = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('dispatcher.requests.cancel', ['serviceRequest' => $assignedRequest, 'status' => RequestStatus::Assigned->value]));

        $cancelResponse->assertRedirect(route('dispatcher.dashboard', ['status' => RequestStatus::Assigned->value]));

        $assignedFilteredAfterCancel = $this->withSession(['auth_user_id' => $dispatcher->id])
            ->get(route('dispatcher.dashboard', ['status' => RequestStatus::Assigned->value]));

        $assignedFilteredAfterCancel->assertOk();
        $assignedFilteredAfterCancel->assertDontSee($assignedRequest->client_name);

        $this->assertDatabaseHas('requests', [
            'id' => $newRequest->id,
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
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
