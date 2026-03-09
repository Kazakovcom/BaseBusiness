<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_can_be_created_with_new_status(): void
    {
        $response = $this->post('/requests', [
            'client_name' => 'Тестовый клиент',
            'phone' => '+7 900 000-00-00',
            'address' => 'Тестовый адрес',
            'problem_text' => 'Тестовая проблема',
        ]);

        $response->assertRedirect(route('requests.create'));

        $this->assertDatabaseHas('requests', [
            'client_name' => 'Тестовый клиент',
            'status' => RequestStatus::New->value,
        ]);

        $request = ServiceRequest::query()->where('client_name', 'Тестовый клиент')->first();
        $this->assertNotNull($request);
        $this->assertSame(RequestStatus::New->value, $request->status);
    }
}
