<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Event;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động khoá sự kiện khi thời gian hiện tại lớn hơn thời gian kết thúc sự kiện
Schedule::call(function () {
    Event::where('end_time', '<', now())
         ->where('status', '!=', 'done')
         ->update(['status' => 'done']);
})->everyMinute();
