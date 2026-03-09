<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function create(): View
    {
        return view('requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'address' => ['required', 'string'],
            'problem_text' => ['required', 'string'],
        ]);

        ServiceRequest::query()->create([
            ...$validated,
            'status' => RequestStatus::New->value,
            'assigned_to' => null,
        ]);

        return redirect()->route('requests.create')->with('status', 'Заявка создана.');
    }
}
