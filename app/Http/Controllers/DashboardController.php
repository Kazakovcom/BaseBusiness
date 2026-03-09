<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function home(Request $request): View
    {
        $currentUser = null;

        if ($request->session()->has('auth_user_id')) {
            $currentUser = User::query()->find($request->session()->get('auth_user_id'));
        }

        return view('home', compact('currentUser'));
    }

    public function dispatcher(Request $request): View
    {
        return view('dispatcher.index', [
            'currentUser' => $request->attributes->get('authUser'),
        ]);
    }

    public function master(Request $request): View
    {
        return view('master.index', [
            'currentUser' => $request->attributes->get('authUser'),
        ]);
    }
}
