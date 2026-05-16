<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;


use App\Http\Controllers\Api\EventController;



Route::get('/events', [EventController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events/{id}/register', [EventController::class, 'register']);
Route::post('/events/{id}/review', [EventController::class, 'review']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

Route::get('/categories', [CategoryController::class, 'index']);

// Lấy danh sách sự kiện theo ID của danh mục
Route::get('/categories/{id}/events', [CategoryController::class, 'getEvents']);
