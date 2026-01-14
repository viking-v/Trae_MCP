<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\Admin\ActivationController as AdminActivationController;
use App\Http\Controllers\Api\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });

    Route::patch('profile', [AuthController::class, 'updateProfile']);
    Route::post('password', [AuthController::class, 'changePassword']);

    Route::get('invite-codes/me', [TeamController::class, 'inviteCodes']);
    Route::get('team/me', [TeamController::class, 'teamSummary']);
    Route::get('team/me/tree', [TeamController::class, 'teamTree']);

    Route::get('activations/me', [ActivationController::class, 'index']);
    Route::post('activations', [ActivationController::class, 'store']);

    Route::get('commissions/me', [CommissionController::class, 'me']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show']);

    Route::get('users', [UserController::class, 'index']);
    Route::patch('users/{user}', [UserController::class, 'update']);

    Route::get('activations', [AdminActivationController::class, 'index']);
    Route::post('activations/{activation}/approve', [AdminActivationController::class, 'approve']);
    Route::post('activations/{activation}/reject', [AdminActivationController::class, 'reject']);

    Route::get('commissions', [AdminCommissionController::class, 'index']);
});
