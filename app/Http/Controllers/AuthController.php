<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $users = User::query()->orderBy('id')->get();

        return view('auth.login', compact('users'));
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        $request->session()->put('auth_user_id', $user->id);

        return $user->role === 'dispatcher'
            ? redirect()->route('dispatcher.dashboard')
            : redirect()->route('master.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('auth_user_id');

        return redirect()->route('login');
    }
}
