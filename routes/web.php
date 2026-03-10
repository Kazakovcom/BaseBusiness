<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatcherController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'home'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
Route::post('/requests', [ServiceRequestController::class, 'store'])->name('requests.store');

Route::middleware(['auth.session', 'role:dispatcher'])->group(function (): void {
    Route::get('/dispatcher', [DispatcherController::class, 'index'])->name('dispatcher.dashboard');
    Route::post('/dispatcher/requests/{serviceRequest}/assign', [DispatcherController::class, 'assign'])->name('dispatcher.requests.assign');
    Route::post('/dispatcher/requests/{serviceRequest}/cancel', [DispatcherController::class, 'cancel'])->name('dispatcher.requests.cancel');
});

Route::middleware(['auth.session', 'role:master'])->group(function (): void {
    Route::get('/master', [DashboardController::class, 'master'])->name('master.dashboard');
});
