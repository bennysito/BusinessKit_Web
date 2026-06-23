<?php

use App\Http\Controllers\Api\Attendance\AttendanceController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Hr\DepartmentController;
use App\Http\Controllers\Api\Hr\EmployeeController;
use App\Http\Controllers\Api\Hr\PositionController;
use App\Http\Controllers\Api\Leave\LeaveRequestController;
use App\Http\Controllers\Api\Leave\LeaveTypeController;
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

    Route::apiResource('leave-types', LeaveTypeController::class)
        ->parameters(['leave-types' => 'leaveType'])
        ->middleware('role:admin|hr');

    Route::apiResource('leave-requests', LeaveRequestController::class)
        ->only(['index', 'show', 'store'])
        ->parameters(['leave-requests' => 'leaveRequest'])
        ->middleware('permission:leave.request|leave.approve');

    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->middleware('permission:leave.request');

    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->middleware('permission:leave.approve');

    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->middleware('permission:leave.approve');

    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->middleware('permission:attendance.manage');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->middleware('permission:attendance.manage');
    Route::get('attendance', [AttendanceController::class, 'index'])
        ->middleware('permission:attendance.manage');
    Route::get('attendance/summary', [AttendanceController::class, 'summary'])
        ->middleware('permission:attendance.manage');
});
