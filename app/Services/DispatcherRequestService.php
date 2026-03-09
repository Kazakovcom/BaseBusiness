<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;

class DispatcherRequestService
{
    public function assignMaster(ServiceRequest $serviceRequest, int $masterId): array
    {
        if ($serviceRequest->status !== RequestStatus::New->value) {
            return [
                'ok' => false,
                'message' => 'Назначение возможно только для заявок в статусе new.',
            ];
        }

        $serviceRequest->update([
            'assigned_to' => $masterId,
            'status' => RequestStatus::Assigned->value,
        ]);

        return [
            'ok' => true,
            'message' => 'Мастер успешно назначен.',
        ];
    }

    public function cancel(ServiceRequest $serviceRequest): array
    {
        $cancelableStatuses = [
            RequestStatus::New->value,
            RequestStatus::Assigned->value,
        ];

        if (! in_array($serviceRequest->status, $cancelableStatuses, true)) {
            return [
                'ok' => false,
                'message' => 'Отмена возможна только для заявок в статусах new или assigned.',
            ];
        }

        $serviceRequest->update([
            'status' => RequestStatus::Canceled->value,
            'assigned_to' => null,
        ]);

        return [
            'ok' => true,
            'message' => 'Заявка успешно отменена.',
        ];
    }
}
