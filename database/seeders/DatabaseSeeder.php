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

        // ── 4. ADDITIONAL ORGANIZERS & EVENTS ─────────────────
        DB::table('users')->insertOrIgnore([
            [
                'id'         => '4',
                'name'       => 'PNV EVENT CLUB',
                'email'      => 'pnv@test.com',
                'password'   => Hash::make('password123'),
                'role_id'    => 2,
                'phone'      => '0901234564',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => '5',
                'name'       => 'DA NANG YOUTH',
                'email'      => 'youth@test.com',
                'password'   => Hash::make('password123'),
                'role_id'    => 2,
                'phone'      => '0901234565',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => '6',
                'name'       => 'FPT EVENT TEAM',
                'email'      => 'fpt@test.com',
                'password'   => Hash::make('password123'),
                'role_id'    => 2,
                'phone'      => '0901234566',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $additionalEvents = [
            // PNV EVENT CLUB
            [
                'organizer_id' => '4',
                'category_id' => 1,
                'title' => 'Summer Music Festival',
                'description' => 'Music festival for students.',
                'location' => 'Da Nang University',
                'start_time' => '2026-06-15 18:00:00',
                'end_time' => '2026-06-15 22:00:00',
                'registration_deadline' => '2026-06-10 23:59:59',
                'capacity' => 100,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '4',
                'category_id' => 5,
                'title' => 'Tech Career Workshop',
                'description' => 'Workshop for IT students.',
                'location' => 'PNV Center',
                'start_time' => '2026-06-20 08:00:00',
                'end_time' => '2026-06-20 11:00:00',
                'registration_deadline' => '2026-06-18 23:59:59',
                'capacity' => 60,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '4',
                'category_id' => 6,
                'title' => 'Volunteer Day',
                'description' => 'Community volunteer activities.',
                'location' => 'Son Tra',
                'start_time' => '2026-06-25 07:00:00',
                'end_time' => '2026-06-25 16:00:00',
                'registration_deadline' => '2026-06-22 23:59:59',
                'capacity' => 80,
                'status' => 'cancelled',
                'cancel_reason' => 'Bad weather',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '4',
                'category_id' => 2,
                'title' => 'Football Tournament',
                'description' => 'Student football competition.',
                'location' => 'Hoa Xuan Stadium',
                'start_time' => '2026-07-01 07:00:00',
                'end_time' => '2026-07-01 18:00:00',
                'registration_deadline' => '2026-06-28 23:59:59',
                'capacity' => 120,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '4',
                'category_id' => 3,
                'title' => 'Food Festival',
                'description' => 'Street food experience.',
                'location' => 'Dragon Bridge',
                'start_time' => '2026-07-05 17:00:00',
                'end_time' => '2026-07-05 22:00:00',
                'registration_deadline' => '2026-07-02 23:59:59',
                'capacity' => 150,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '4',
                'category_id' => 4,
                'title' => 'Art Exhibition',
                'description' => 'Modern art showcase.',
                'location' => 'Da Nang Museum',
                'start_time' => '2026-07-10 09:00:00',
                'end_time' => '2026-07-10 17:00:00',
                'registration_deadline' => '2026-07-07 23:59:59',
                'capacity' => 70,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DA NANG YOUTH
            [
                'organizer_id' => '5',
                'category_id' => 6,
                'title' => 'Beach Cleanup',
                'description' => 'Environmental protection event.',
                'location' => 'My Khe Beach',
                'start_time' => '2026-07-12 06:00:00',
                'end_time' => '2026-07-12 11:00:00',
                'registration_deadline' => '2026-07-10 23:59:59',
                'capacity' => 90,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '5',
                'category_id' => 5,
                'title' => 'English Speaking Day',
                'description' => 'Practice communication skills.',
                'location' => 'Youth Center',
                'start_time' => '2026-07-15 08:00:00',
                'end_time' => '2026-07-15 12:00:00',
                'registration_deadline' => '2026-07-13 23:59:59',
                'capacity' => 50,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '5',
                'category_id' => 2,
                'title' => 'Basketball Contest',
                'description' => 'Youth basketball event.',
                'location' => 'Sports Center',
                'start_time' => '2026-07-18 08:00:00',
                'end_time' => '2026-07-18 18:00:00',
                'registration_deadline' => '2026-07-16 23:59:59',
                'capacity' => 100,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '5',
                'category_id' => 1,
                'title' => 'Acoustic Night',
                'description' => 'Music night for students.',
                'location' => 'Coffee House',
                'start_time' => '2026-07-20 19:00:00',
                'end_time' => '2026-07-20 22:00:00',
                'registration_deadline' => '2026-07-18 23:59:59',
                'capacity' => 60,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '5',
                'category_id' => 3,
                'title' => 'Cooking Challenge',
                'description' => 'Cooking competition.',
                'location' => 'Hai Chau Hall',
                'start_time' => '2026-07-22 14:00:00',
                'end_time' => '2026-07-22 18:00:00',
                'registration_deadline' => '2026-07-20 23:59:59',
                'capacity' => 40,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '5',
                'category_id' => 4,
                'title' => 'Painting Workshop',
                'description' => 'Creative painting class.',
                'location' => 'Art House',
                'start_time' => '2026-07-25 09:00:00',
                'end_time' => '2026-07-25 12:00:00',
                'registration_deadline' => '2026-07-22 23:59:59',
                'capacity' => 35,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // FPT EVENT TEAM
            [
                'organizer_id' => '6',
                'category_id' => 5,
                'title' => 'AI Seminar',
                'description' => 'Artificial Intelligence seminar.',
                'location' => 'FPT University',
                'start_time' => '2026-08-01 08:00:00',
                'end_time' => '2026-08-01 12:00:00',
                'registration_deadline' => '2026-07-28 23:59:59',
                'capacity' => 200,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 2,
                'title' => 'Badminton Cup',
                'description' => 'Badminton competition.',
                'location' => 'FPT Gym',
                'start_time' => '2026-08-03 07:00:00',
                'end_time' => '2026-08-03 17:00:00',
                'registration_deadline' => '2026-08-01 23:59:59',
                'capacity' => 90,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 1,
                'title' => 'Rock Show',
                'description' => 'Live rock performance.',
                'location' => 'FPT Arena',
                'start_time' => '2026-08-05 18:00:00',
                'end_time' => '2026-08-05 23:00:00',
                'registration_deadline' => '2026-08-02 23:59:59',
                'capacity' => 250,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 6,
                'title' => 'Blood Donation',
                'description' => 'Community charity event.',
                'location' => 'FPT Campus',
                'start_time' => '2026-08-08 08:00:00',
                'end_time' => '2026-08-08 16:00:00',
                'registration_deadline' => '2026-08-06 23:59:59',
                'capacity' => 100,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 3,
                'title' => 'BBQ Party',
                'description' => 'Outdoor BBQ gathering.',
                'location' => 'Hoa Xuan Park',
                'start_time' => '2026-08-10 17:00:00',
                'end_time' => '2026-08-10 22:00:00',
                'registration_deadline' => '2026-08-08 23:59:59',
                'capacity' => 120,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 4,
                'title' => 'Photography Contest',
                'description' => 'Photography competition.',
                'location' => 'City Library',
                'start_time' => '2026-08-12 09:00:00',
                'end_time' => '2026-08-12 17:00:00',
                'registration_deadline' => '2026-08-10 23:59:59',
                'capacity' => 75,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 5,
                'title' => 'Startup Workshop',
                'description' => 'Business startup sharing.',
                'location' => 'Innovation Hub',
                'start_time' => '2026-08-15 08:00:00',
                'end_time' => '2026-08-15 12:00:00',
                'registration_deadline' => '2026-08-12 23:59:59',
                'capacity' => 110,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => '6',
                'category_id' => 2,
                'title' => 'Running Marathon',
                'description' => 'Community running event.',
                'location' => 'East Sea Park',
                'start_time' => '2026-08-18 05:00:00',
                'end_time' => '2026-08-18 10:00:00',
                'registration_deadline' => '2026-08-15 23:59:59',
                'capacity' => 300,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($additionalEvents as &$evt) {
            if (!array_key_exists('cancel_reason', $evt)) {
                $evt['cancel_reason'] = null;
            }
            if (!array_key_exists('image', $evt)) {
                $evt['image'] = null;
            }
        }

        DB::table('events')->insert($additionalEvents);

        $this->command->info('✅ Seeded: 6 categories, 5 users, 26 events');
    }
}
