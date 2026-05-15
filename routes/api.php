<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CategoryController;

Route::get('/events', [EventController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events/{id}/register', [EventController::class, 'register']);
Route::post('/events/{id}/review', [EventController::class, 'review']);