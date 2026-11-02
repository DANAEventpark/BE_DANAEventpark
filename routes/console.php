<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\Event;
use App\Models\Registration;
use App\Mail\EventReminderMail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động khoá sự kiện khi thời gian hiện tại lớn hơn thời gian kết thúc sự kiện
Schedule::call(function () {
    Event::where('end_time', '<', now())
         ->where('status', '!=', 'done')
         ->update(['status' => 'done']);
})->everyMinute();

// Gửi email nhắc nhở 1 ngày trước khi sự kiện diễn ra (chạy mỗi giờ để đảm bảo không bỏ sót)
Schedule::call(function () {
    $now        = now();
    $from       = $now->copy()->addHours(23);   // Sự kiện bắt đầu trong khoảng 23–25 giờ tới
    $to         = $now->copy()->addHours(25);

    // Lấy các sự kiện published sắp diễn ra trong khoảng thời gian trên
    $upcomingEvents = Event::where('status', 'published')
        ->whereBetween('start_time', [$from, $to])
        ->with('category')
        ->get();

    foreach ($upcomingEvents as $event) {
        // Dùng Cache để tránh gửi email trùng lặp cho cùng 1 sự kiện
        $cacheKey = 'reminder_sent_event_' . $event->id;
        if (Cache::has($cacheKey)) {
            continue;
        }

        // Lấy tất cả người đăng ký đã được duyệt (approved)
        $approvedRegistrations = Registration::where('event_id', $event->id)
            ->where('status', 'approved')
            ->with('user:id,name,email')
            ->get();

        foreach ($approvedRegistrations as $registration) {
            if ($registration->user && $registration->user->email) {
                Mail::to($registration->user->email)
                    ->queue(new EventReminderMail($event, $registration->user));
            }
        }

        // Đánh dấu đã gửi trong 26 giờ để tránh gửi lại
        Cache::put($cacheKey, true, now()->addHours(26));
    }
})->hourly();

