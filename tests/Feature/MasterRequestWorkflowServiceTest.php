<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\MasterRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterRequestWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_take_in_work_allows_first_call_and_rejects_second_call_for_same_request(): void
    {
        $master = User::query()->create([
            'name' => 'Мастер Race',
            'email' => 'master.race@test.local',
            'role' => UserRole::Master->value,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'client_name' => 'Клиент race',
            'phone' => '+7 700 600-00-01',
            'address' => 'Адрес race',
            'problem_text' => 'Проблема race',
            'status' => RequestStatus::Assigned->value,
            'assigned_to' => $master->id,
        ]);

        $workflowService = $this->app->make(MasterRequestWorkflowService::class);

        $firstResult = $workflowService->takeInWork($serviceRequest, $master);
        $secondResult = $workflowService->takeInWork($serviceRequest, $master);

        $this->assertTrue($firstResult['ok']);
        $this->assertSame('Заявка взята в работу.', $firstResult['message']);

        $this->assertFalse($secondResult['ok']);
        $this->assertSame(409, $secondResult['status']);
        $this->assertSame('Взять в работу можно только заявку в статусе "Назначена".', $secondResult['message']);

        $this->assertDatabaseHas('requests', [
            'id' => $serviceRequest->id,
            'status' => RequestStatus::InProgress->value,
            'assigned_to' => $master->id,
        ]);
    }
}
