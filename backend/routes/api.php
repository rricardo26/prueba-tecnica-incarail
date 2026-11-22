<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Los 5 endpoints obligatorios
    Route::get('/competitions', [CompetitionController::class, 'index'])
        ->middleware('can:competitions.read');
    Route::get('/competitions/{competition_id}', [CompetitionController::class, 'show'])
        ->middleware('can:competitions.read');
    Route::get('/team', [TeamController::class, 'index'])
        ->middleware('can:teams.read');
    Route::get('/team/{team_id}', [TeamController::class, 'show'])
        ->middleware('can:teams.read');
    Route::get('/players', [PlayerController::class, 'index'])
        ->middleware('can:players.read');

    // O18
    Route::post('/competitions/{competition_id}/sync-async', [CompetitionController::class, 'syncAsync'])
        ->middleware('can:competitions.import');

    // B2
    Route::middleware('can:users.manage')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    Route::middleware('can:roles.manage')->group(function () {
        Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'update']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });
});