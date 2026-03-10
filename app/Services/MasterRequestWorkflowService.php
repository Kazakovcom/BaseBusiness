<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;

class MasterRequestWorkflowService
{
    public function takeInWork(ServiceRequest $serviceRequest, User $master): array
    {
        $updated = ServiceRequest::query()
            ->whereKey($serviceRequest->id)
            ->where('assigned_to', $master->id)
            ->where('status', RequestStatus::Assigned->value)
            ->update([
                'status' => RequestStatus::InProgress->value,
            ]);

        if ($updated === 1) {
            return [
                'ok' => true,
                'message' => 'Заявка взята в работу.',
                'status' => 200,
            ];
        }

        return $this->buildFailureResult($serviceRequest->id, $master, RequestStatus::Assigned, 'Взять в работу можно только заявку в статусе "Назначена".');
    }

    public function complete(ServiceRequest $serviceRequest, User $master): array
    {
        $updated = ServiceRequest::query()
            ->whereKey($serviceRequest->id)
            ->where('assigned_to', $master->id)
            ->where('status', RequestStatus::InProgress->value)
            ->update([
                'status' => RequestStatus::Done->value,
            ]);

        if ($updated === 1) {
            return [
                'ok' => true,
                'message' => 'Заявка завершена.',
                'status' => 200,
            ];
        }

        return $this->buildFailureResult($serviceRequest->id, $master, RequestStatus::InProgress, 'Завершить можно только заявку в статусе "В работе".');
    }

    private function buildFailureResult(int $serviceRequestId, User $master, RequestStatus $expectedStatus, string $invalidStatusMessage): array
    {
        $freshRequest = ServiceRequest::query()->findOrFail($serviceRequestId);

        if ((int) $freshRequest->assigned_to !== (int) $master->id) {
            return [
                'ok' => false,
                'message' => 'Эта заявка не назначена вам.',
                'status' => 403,
            ];
        }

        if ($freshRequest->status !== $expectedStatus->value) {
            return [
                'ok' => false,
                'message' => $invalidStatusMessage,
                'status' => 409,
            ];
        }

        return [
            'ok' => false,
            'message' => 'Операция не выполнена из-за изменения состояния заявки.',
            'status' => 409,
        ];
    }
}
