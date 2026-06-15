<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/auth/session', function (Request $request) {
    return response()->json([
        'message' => 'Authenticated',
        'user' => $request->user(),
    ]);
})->middleware('auth:sanctum');




// PUBLIC ROUTES
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);


// AUTHENTICATED ROUTES
Route::group(['auth:sanctum'], function () {

    // AUTHENTICATION
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});
