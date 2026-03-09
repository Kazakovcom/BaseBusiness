<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DispatcherRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispatcherController extends Controller
{
    public function __construct(private readonly DispatcherRequestService $dispatcherRequestService)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusValues = RequestStatus::values();

        $requestsQuery = ServiceRequest::query()
            ->with('assignedMaster')
            ->orderByDesc('id');

        if (is_string($status) && in_array($status, $statusValues, true)) {
            $requestsQuery->where('status', $status);
        }

        return view('dispatcher.index', [
            'currentUser' => $request->attributes->get('authUser'),
            'requests' => $requestsQuery->get(),
            'masters' => User::query()->where('role', UserRole::Master->value)->orderBy('name')->get(),
            'statuses' => $statusValues,
            'selectedStatus' => is_string($status) && in_array($status, $statusValues, true) ? $status : '',
        ]);
    }

    public function assign(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'master_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::Master->value)),
            ],
        ]);

        $result = $this->dispatcherRequestService->assignMaster($serviceRequest, (int) $validated['master_id']);

        return redirect()->route('dispatcher.dashboard', $request->query())
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $result = $this->dispatcherRequestService->cancel($serviceRequest);

        return redirect()->route('dispatcher.dashboard', $request->query())
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
