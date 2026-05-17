<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CategoryController;

// Auth routes (REQ_01 + REQ_02)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Event Detail (REQ_09)
Route::get('/events/{id}', [EventController::class, 'show']);

// Categories (REQ_13)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}/events', [CategoryController::class, 'getEvents']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/events/{id}/register', [RegistrationController::class, 'store']);
    Route::post('/events/{id}/reviews', [ReviewController::class, 'store']);
});