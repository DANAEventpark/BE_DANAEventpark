<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/seed-magic', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return 'Seeded successfully! Vui lòng quay lại Vercel để kiểm tra.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
