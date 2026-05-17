<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Api\EventController;

// --- ROUTE THỐNG KÊ HỆ THỐNG (Độc lập, không lo đụng hàng) ---
Route::get('/system-stats', [EventController::class, 'getSystemStats']);


// --- ROUTES CHO EVENTS ---
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events/{id}/register', [EventController::class, 'register']);
Route::post('/events/{id}/review', [EventController::class, 'review']);


// --- ROUTES CHO CATEGORIES ---
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}/events', [CategoryController::class, 'getEvents']);


// --- AUTHENTICATION MIDDLEWARE ---
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});
