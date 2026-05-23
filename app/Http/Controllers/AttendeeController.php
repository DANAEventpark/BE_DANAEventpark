<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

/**
 * AttendeeController — REQ_11
 * Xử lý các API cho Attendee Dashboard:
 *   - Thống kê (đã đăng ký, waitlist, đã huỷ)
 *   - Danh sách sự kiện đã đăng ký (approved)
 *   - Danh sách waitlist (pending)
 *   - Danh sách đã huỷ (cancelled)
 */
class AttendeeController extends Controller
{
    /**
     * GET /api/attendee/dashboard/stats
     * Trả về số lượng sự kiện theo từng trạng thái đăng ký của người dùng hiện tại.
     */
    public function getDashboardStats()
    {
        $user = Auth::user();

        $registered = Registration::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $waitlist = Registration::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $cancelled = Registration::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'registered' => $registered,
                'waitlist'   => $waitlist,
                'cancelled'  => $cancelled,
            ],
        ]);
    }

    /**
     * GET /api/attendee/dashboard/registrations
     * Danh sách sự kiện đã đăng ký (registration.status = approved).
     * Hỗ trợ: search (title/location), category_id, year + phân trang.
     */
    public function getRegistrations(Request $request)
    {
        $user  = Auth::user();
        $query = $this->baseQuery($user->id, 'approved');

        $query = $this->applyFilters($query, $request);

        $registrations = $query->orderBy('registrations.created_at', 'desc')->paginate(6);

        return response()->json($registrations);
    }

    /**
     * GET /api/attendee/dashboard/waitlist
     * Danh sách đang chờ (registration.status = pending).
     */
    public function getWaitlist(Request $request)
    {
        $user  = Auth::user();
        $query = $this->baseQuery($user->id, 'pending');

        $query = $this->applyFilters($query, $request);

        $registrations = $query->orderBy('registrations.created_at', 'desc')->paginate(6);

        return response()->json($registrations);
    }

    /**
     * GET /api/attendee/dashboard/cancelled
     * Danh sách đã huỷ (registration.status = cancelled).
     */
    public function getCancelledRegistrations(Request $request)
    {
        $user  = Auth::user();
        $query = $this->baseQuery($user->id, 'cancelled');

        $query = $this->applyFilters($query, $request);

        $registrations = $query->orderBy('registrations.created_at', 'desc')->paginate(6);

        return response()->json($registrations);
    }

    // ─── Private Helpers ────────────────────────────────────────────────────

    /**
     * Query cơ bản: lấy registrations + eager load event (category, organizer).
     */
    private function baseQuery(string $userId, string $status)
    {
        return Registration::with([
            'event' => function ($q) {
                $q->with([
                    'category:id,name,image',
                    'organizer:id,name',
                ]);
            },
        ])
        ->where('user_id', $userId)
        ->where('status', $status);
    }

    /**
     * Áp dụng bộ lọc tìm kiếm, danh mục và năm.
     */
    private function applyFilters($query, Request $request)
    {
        // Tìm kiếm theo tiêu đề hoặc địa điểm sự kiện
        if ($search = $request->query('search')) {
            $query->whereHas('event', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%');
            });
        }

        // Lọc theo danh mục
        if ($categoryId = $request->query('category_id')) {
            if ($categoryId !== 'all') {
                $query->whereHas('event', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }
        }

        // Lọc theo năm của sự kiện
        if ($year = $request->query('year')) {
            if ($year !== 'all') {
                $query->whereHas('event', function ($q) use ($year) {
                    $q->whereYear('start_time', $year);
                });
            }
        }

        return $query;
    }
}
