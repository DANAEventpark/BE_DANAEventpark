<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 0. ROLES ───────────────────────────────────────────
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'attendee'],
            ['id' => 2, 'name' => 'organizer'],
        ]);

        // ── 1. CATEGORIES ─────────────────────────────────────
        $categories = [
            ['name' => 'Âm nhạc',    'image' => 'music.jpg'],
            ['name' => 'Thể thao',   'image' => 'sports.jpg'],
            ['name' => 'Nghệ thuật', 'image' => 'art.jpg'],
            ['name' => 'Ẩm thực',    'image' => 'food.jpg'],
            ['name' => 'Giáo dục',   'image' => 'education.jpg'],
            ['name' => 'Cộng đồng',  'image' => 'community.jpg'],
        ];

        foreach ($categories as &$cat) {
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
        }
        DB::table('categories')->insert($categories);

        // ── 2. USERS ───────────────────────────────────────────
        $organizerId = Str::uuid()->toString();
        $attendeeId  = Str::uuid()->toString();

        DB::table('users')->insert([
            [
                'id'         => $organizerId,
                'name'       => 'Organizer DANAEventSpark',
                'email'      => 'organizer@test.com',
                'password'   => Hash::make('password123'),
                'role_id'    => 2,
                'phone'      => '0901234567',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => $attendeeId,
                'name'       => 'Attendee Test User',
                'email'      => 'attendee@test.com',
                'password'   => Hash::make('password123'),
                'role_id'    => 1,
                'phone'      => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ── 3. EVENTS ─────────────────────────────────────────
        $events = [
            [
                'title'                 => 'Đêm nhạc Mỹ Khê 2026',
                'description'           => 'Đêm nhạc ngoài trời bên bờ biển Mỹ Khê với các nghệ sĩ nổi tiếng.',
                'start_time'            => '2026-06-15 19:00:00',
                'end_time'              => '2026-06-15 22:30:00',
                'location'              => 'Bãi biển Mỹ Khê, Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 500,
                'category_id'           => 1, // Âm nhạc
                'image'                 => null,
                'registration_deadline' => '2026-06-10 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
            [
                'title'                 => 'Giải chạy bộ Đà Nẵng Marathon 2026',
                'description'           => 'Giải chạy bộ quốc tế với hơn 5000 vận động viên tham dự.',
                'start_time'            => '2026-07-05 05:30:00',
                'end_time'              => '2026-07-05 10:00:00',
                'location'              => 'Công viên APEC, Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 5000,
                'category_id'           => 2, // Thể thao
                'image'                 => null,
                'registration_deadline' => '2026-06-30 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
            [
                'title'                 => 'Triển lãm Mỹ thuật Đương đại',
                'description'           => 'Triển lãm các tác phẩm nghệ thuật đương đại của các hoạ sĩ trẻ Việt Nam.',
                'start_time'            => '2026-06-20 09:00:00',
                'end_time'              => '2026-06-25 20:00:00',
                'location'              => 'Bảo tàng Mỹ thuật Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 200,
                'category_id'           => 3, // Nghệ thuật
                'image'                 => null,
                'registration_deadline' => '2026-06-18 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
            [
                'title'                 => 'Lễ hội Ẩm thực Quốc tế Đà Nẵng',
                'description'           => 'Hội tụ hơn 50 gian hàng ẩm thực từ 20 quốc gia trên thế giới.',
                'start_time'            => '2026-08-10 10:00:00',
                'end_time'              => '2026-08-12 22:00:00',
                'location'              => 'Quảng trường 29-3, Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 10000,
                'category_id'           => 4, // Ẩm thực
                'image'                 => null,
                'registration_deadline' => '2026-08-08 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
            [
                'title'                 => 'Workshop Lập trình Web cho người mới',
                'description'           => 'Khoá học thực hành HTML, CSS, JavaScript dành cho người bắt đầu.',
                'start_time'            => '2026-06-28 08:00:00',
                'end_time'              => '2026-06-28 17:00:00',
                'location'              => 'Coworking Space The Hive, Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 30,
                'category_id'           => 5, // Giáo dục
                'image'                 => null,
                'registration_deadline' => '2026-06-25 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
            [
                'title'                 => 'Ngày hội tình nguyện Đà Nẵng Xanh',
                'description'           => 'Cùng nhau trồng cây, dọn bãi biển và lan toả yêu thương cộng đồng.',
                'start_time'            => '2026-07-20 07:00:00',
                'end_time'              => '2026-07-20 11:00:00',
                'location'              => 'Bãi biển Phạm Văn Đồng, Đà Nẵng',
                'organizer_id'          => $organizerId,
                'status'                => 'published',
                'capacity'              => 300,
                'category_id'           => 6, // Cộng đồng
                'image'                 => null,
                'registration_deadline' => '2026-07-18 23:59:00',
                'created_at'            => now(),
                'updated_at'            => now(),
            ],
        ];

        DB::table('events')->insert($events);

        $this->command->info('✅ Seeded: 6 categories, 2 users, 6 events');
    }
}
