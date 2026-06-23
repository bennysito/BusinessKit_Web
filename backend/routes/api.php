<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Hr\DepartmentController;
use App\Http\Controllers\Api\Hr\EmployeeController;
use App\Http\Controllers\Api\Hr\PositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('departments', DepartmentController::class)
        ->middleware('permission:departments.manage');

    Route::apiResource('positions', PositionController::class)
        ->middleware('permission:positions.manage');

    Route::apiResource('employees', EmployeeController::class)
        ->only(['index', 'show'])
        ->middleware('permission:employees.view');

    Route::apiResource('employees', EmployeeController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('permission:employees.manage');
});
