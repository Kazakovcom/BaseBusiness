<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_sees_only_requests_assigned_to_him(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер 1',
            'email' => 'master.panel@test.local',
            'role' => UserRole::Master->value,
        ]);

        $otherMaster = User::query()->create([
            'name' => 'Мастер 2',
            'email' => 'other.master.panel@test.local',
            'role' => UserRole::Master->value,
        ]);

        $ownAssignedRequest = ServiceRequest::query()->create([
            'client_name' => 'Мой назначенный клиент',
            'phone' => '+7 700 100-00-01',
            'address' => 'Мой адрес 1',
            'problem_text' => 'Моя проблема 1',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $ownInProgressRequest = ServiceRequest::query()->create([
            'client_name' => 'Мой активный клиент',
            'phone' => '+7 700 100-00-02',
            'address' => 'Мой адрес 2',
            'problem_text' => 'Моя проблема 2',
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);

        $foreignRequest = ServiceRequest::query()->create([
            'client_name' => 'Чужой клиент',
            'phone' => '+7 700 100-00-03',
            'address' => 'Чужой адрес',
            'problem_text' => 'Чужая проблема',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $otherMaster->id,
        ]);

        $unassignedRequest = ServiceRequest::query()->create([
            'client_name' => 'Неназначенный клиент',
            'phone' => '+7 700 100-00-04',
            'address' => 'Свободный адрес',
            'problem_text' => 'Свободная проблема',
            'status' => RequestStatus::New->value,
            'assigned_to' => null,
        ]);

        $response = $this->withSession(['auth_user_id' => $master->id])->get(route('master.dashboard'));

        $response->assertOk();
        $response->assertSee((string) $ownAssignedRequest->id);
        $response->assertSee($ownAssignedRequest->client_name);
        $response->assertSee($ownAssignedRequest->phone);
        $response->assertSee($ownAssignedRequest->address);
        $response->assertSee($ownAssignedRequest->problem_text);
        $response->assertSee($ownInProgressRequest->client_name);
        $response->assertDontSee($foreignRequest->client_name);
        $response->assertDontSee($unassignedRequest->client_name);
    }

    public function test_master_can_take_his_assigned_request_into_work(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Take',
            'email' => 'master.take@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент take',
            'phone' => '+7 700 200-00-01',
            'address' => 'Адрес take',
            'problem_text' => 'Проблема take',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $response = $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.take', ['serviceRequest' => $serviceRequest]));

        $response->assertRedirect(route('master.dashboard'));
        $response->assertSessionHas('success', 'Заявка взята в работу.');

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_master_take_json_returns_conflict_on_repeated_call(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Take JSON',
            'email' => 'master.take.json@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент take json',
            'phone' => '+7 700 210-00-01',
            'address' => 'Адрес take json',
            'problem_text' => 'Проблема take json',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $firstResponse = $this->withSession(['auth_user_id' => $master->id])
            ->postJson(route('master.requests.take', ['serviceRequest' => $serviceRequest]));

        $firstResponse->assertOk();
        $firstResponse->assertJson([
            'message' => 'Заявка взята в работу.',
        ]);

        $secondResponse = $this->withSession(['auth_user_id' => $master->id])
            ->postJson(route('master.requests.take', ['serviceRequest' => $serviceRequest]));

        $secondResponse->assertStatus(409);
        $secondResponse->assertJson([
            'message' => 'Взять в работу можно только заявку в статусе "Назначена".',
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_master_can_complete_his_in_progress_request(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Complete',
            'email' => 'master.complete@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент complete',
            'phone' => '+7 700 300-00-01',
            'address' => 'Адрес complete',
            'problem_text' => 'Проблема complete',
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);

        $response = $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.complete', ['serviceRequest' => $serviceRequest]));

        $response->assertRedirect(route('master.dashboard'));
        $response->assertSessionHas('success', 'Заявка завершена.');

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::Done->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_master_cannot_take_foreign_or_invalid_status_request(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Negative',
            'email' => 'master.negative.panel@test.local',
            'role' => UserRole::Master->value,
        ]);

        $otherMaster = User::query()->create([
            'name' => 'Чужой мастер',
            'email' => 'other.master.negative.panel@test.local',
            'role' => UserRole::Master->value,
        ]);

        $foreignRequest = ServiceRequest::query()->create([
            'client_name' => 'Чужой клиент для take',
            'phone' => '+7 700 400-00-01',
            'address' => 'Чужой адрес take',
            'problem_text' => 'Чужая проблема take',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $otherMaster->id,
        ]);

        $invalidStatusRequest = ServiceRequest::query()->create([
            'client_name' => 'Мой клиент в работе',
            'phone' => '+7 700 400-00-02',
            'address' => 'Мой адрес invalid',
            'problem_text' => 'Моя проблема invalid',
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);

        $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.take', ['serviceRequest' => $foreignRequest]))
            ->assertRedirect(route('master.dashboard'))
            ->assertSessionHas('error', 'Эта заявка не назначена вам.');

        $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.take', ['serviceRequest' => $invalidStatusRequest]))
            ->assertRedirect(route('master.dashboard'))
            ->assertSessionHas('error', 'Взять в работу можно только заявку в статусе "Назначена".');

        $this->assertDatabaseHas('requests', [
            'id' => $foreignRequest->id,
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $otherMaster->id,
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $invalidStatusRequest->id,
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_dispatcher_cannot_access_master_routes(): void
    {
        $dispatcher = User::query()->create([
            'name' => 'Диспетчер Guard',
            'email' => 'dispatcher.master.guard@test.local',
            'role' => UserRole::Dispatcher->value,
        ]);

        $master = User::query()->create([
            'name' => 'Мастер Guard',
            'email' => 'master.guard@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент guard',
            'phone' => '+7 700 500-00-01',
            'address' => 'Адрес guard',
            'problem_text' => 'Проблема guard',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->get(route('master.dashboard'))
            ->assertRedirect(route('home'));

        $this->withSession(['auth_user_id' => $dispatcher->id])
            ->post(route('master.requests.take', ['serviceRequest' => $serviceRequest]))
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);
    }

    public function test_master_cannot_complete_foreign_or_invalid_status_request(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Complete Negative',
            'email' => 'master.complete.negative@test.local',
            'role' => UserRole::Master->value,
        ]);

        $otherMaster = User::query()->create([
            'name' => 'Чужой мастер complete',
            'email' => 'other.master.complete.negative@test.local',
            'role' => UserRole::Master->value,
        ]);

        $foreignRequest = ServiceRequest::query()->create([
            'client_name' => 'Чужой клиент complete',
            'phone' => '+7 700 510-00-01',
            'address' => 'Чужой адрес complete',
            'problem_text' => 'Чужая проблема complete',
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $otherMaster->id,
        ]);

        $invalidStatusRequest = ServiceRequest::query()->create([
            'client_name' => 'Мой клиент не в работе',
            'phone' => '+7 700 510-00-02',
            'address' => 'Мой адрес complete invalid',
            'problem_text' => 'Моя проблема complete invalid',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.complete', ['serviceRequest' => $foreignRequest]))
            ->assertRedirect(route('master.dashboard'))
            ->assertSessionHas('error', 'Эта заявка не назначена вам.');

        $this->withSession(['auth_user_id' => $master->id])
            ->post(route('master.requests.complete', ['serviceRequest' => $invalidStatusRequest]))
            ->assertRedirect(route('master.dashboard'))
            ->assertSessionHas('error', 'Завершить можно только заявку в статусе "В работе".');

        $this->assertDatabaseHas('requests', [
            'id' => $foreignRequest->id,
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $otherMaster->id,
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $invalidStatusRequest->id,
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);
    }
}
