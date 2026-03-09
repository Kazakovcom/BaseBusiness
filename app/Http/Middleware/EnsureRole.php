<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $userId = $request->session()->get('auth_user_id');
        $user = User::query()->find($userId);

        if (! $user || $user->role !== $role) {
            return new RedirectResponse(route('home'));
        }

        $request->attributes->set('authUser', $user);

        return $next($request);
    }
}
