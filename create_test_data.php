<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Event;
use App\Models\Category;
use App\Models\Registration;
use Illuminate\Support\Facades\Hash;

// Tạo Organizer
$org = User::updateOrCreate(
    ['email' => 'test.organizer@gmail.com'],
    [
        'name' => 'Tài khoản Test Organizer',
        'password' => Hash::make('password123'),
        'role_id' => 2
    ]
);

// Tạo Attendee
$att = User::updateOrCreate(
    ['email' => 'test.attendee@gmail.com'],
    [
        'name' => 'Tài khoản Test Attendee',
        'password' => Hash::make('password123'),
        'role_id' => 1
    ]
);

// Tạo một category nếu chưa có
$cat = Category::firstOrCreate(['name' => 'Test Category'], ['image' => 'test.jpg']);

// Tạo Event đã quá hạn để nó tự động bị lock
$event = Event::create([
    'organizer_id' => $org->id,
    'category_id' => $cat->id,
    'title' => 'Sự kiện Test Auto Lock',
    'description' => 'Sự kiện này được tạo riêng để test tính năng Auto Lock',
    'location' => 'Da Nang',
    'start_time' => now()->subDays(3),
    'end_time' => now()->subDays(2), // Quá hạn 2 ngày trước
    'registration_deadline' => now()->subDays(4),
    'capacity' => 100,
    'status' => 'published' // Khởi tạo là published, cron job sẽ update nó thành done
]);

// Đăng ký cho Attendee
Registration::create([
    'user_id' => $att->id,
    'event_id' => $event->id,
    'status' => 'approved'
]);

echo "DONE!";
