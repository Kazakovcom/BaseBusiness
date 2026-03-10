<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\MasterRequestWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterController extends Controller
{
    public function __construct(private readonly MasterRequestWorkflowService $masterRequestWorkflowService)
    {
    }

    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->attributes->get('authUser');

        return view('master.index', [
            'currentUser' => $currentUser,
            'requests' => ServiceRequest::query()
                ->where('assigned_to', $currentUser->id)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function take(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->attributes->get('authUser');

        $result = $this->masterRequestWorkflowService->takeInWork($serviceRequest, $currentUser);

        return $this->buildResponse($request, $result);
    }

    public function complete(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->attributes->get('authUser');

        $result = $this->masterRequestWorkflowService->complete($serviceRequest, $currentUser);

        return $this->buildResponse($request, $result);
    }

    private function buildResponse(Request $request, array $result): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(
                ['message' => $result['message']],
                $result['status']
            );
        }

        return redirect()
            ->route('master.dashboard')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
