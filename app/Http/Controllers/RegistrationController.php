<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function store(Request $request, $eventId)
    {
        $user  = Auth::user();
        $event = Event::findOrFail($eventId);

        // Lấy số ghế muốn đăng ký (mặc định 1, organizer có thể truyền nhiều hơn)
        $seats = (int) $request->input('seats', 1);
        if ($seats < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Số ghế đăng ký phải ít nhất là 1.'
            ], 422);
        }

        // ─── Ràng buộc 1: Kiểm tra đã đăng ký chưa ───────────────────────────
        $existing = Registration::where('event_id', $eventId)
                                ->where('user_id', $user->id)
                                ->first();

        if ($existing) {
            $statusText = match($existing->status) {
                'approved'  => __('messages.approved'),
                'pending'   => __('messages.pending'),
                default     => __('messages.cancelled'),
            };
            return response()->json([
                'success' => false,
                'message' => __('messages.already_registered', ['status' => $statusText])
            ], 400);
        }

        // ─── Ràng buộc 2: Cảnh báo trùng thời gian sự kiện ──────────────────
        // Lấy tất cả sự kiện mà người dùng đã đăng ký (approved/pending)
        $conflictingRegistration = Registration::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereHas('event', function ($q) use ($event) {
                $q->where('id', '!=', $event->id)
                  // Kiểm tra khoảng thời gian sự kiện bị chồng lặp
                  ->where('start_time', '<', $event->end_time)
                  ->where('end_time',   '>', $event->start_time);
            })
            ->with('event:id,title,start_time,end_time')
            ->first();

        if ($conflictingRegistration) {
            $conflict = $conflictingRegistration->event;
            return response()->json([
                'success'           => false,
                'warning'           => true,
                'message'           => 'Cảnh báo: Bạn đã đăng ký sự kiện "' . $conflict->title
                    . '" diễn ra từ ' . $conflict->start_time->format('d/m/Y H:i')
                    . ' đến ' . $conflict->end_time->format('d/m/Y H:i')
                    . ' — trùng thời gian với sự kiện này. Vui lòng kiểm tra lại lịch của bạn.',
                'conflicting_event' => [
                    'id'         => $conflict->id,
                    'title'      => $conflict->title,
                    'start_time' => $conflict->start_time->format('Y-m-d H:i:s'),
                    'end_time'   => $conflict->end_time->format('Y-m-d H:i:s'),
                ],
            ], 409);
        }

        // ─── Ràng buộc 3: Kiểm tra số ghế không vượt giới hạn sức chứa ──────
        $approvedSeats = Registration::where('event_id', $eventId)
                                     ->where('status', 'approved')
                                     ->sum('seats');

        // Số ghế yêu cầu phải nhỏ hơn giới hạn của sự kiện (< capacity, không phải <=)
        if ($seats >= $event->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Số ghế đăng ký (' . $seats . ') phải nhỏ hơn giới hạn đăng ký của sự kiện (' . $event->capacity . ' ghế).'
            ], 422);
        }

        // Xác định status dựa trên chỗ còn trống
        $availableSeats = $event->capacity - $approvedSeats;
        if ($seats <= $availableSeats) {
            $status  = 'approved';
            $message = __('messages.register_event_success');
        } else {
            $status  = 'pending';
            $message = __('messages.register_event_waitlist');
        }

        $registration = Registration::create([
            'event_id' => $eventId,
            'user_id'  => $user->id,
            'status'   => $status,
            'seats'    => $seats,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $registration
        ]);
    }

    public function cancel($eventId)
    {
        $user  = Auth::user();
        $event = Event::findOrFail($eventId);

        // Tìm đăng ký hiện tại có trạng thái approved hoặc pending của người dùng này
        $registration = Registration::where('event_id', $eventId)
                                    ->where('user_id', $user->id)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng ký tham gia sự kiện này hoặc đăng ký đã bị hủy trước đó.'
            ], 400);
        }

        // Kiểm tra thời hạn hủy (chưa tới hạn đóng đăng ký)
        $now      = now();
        $deadline = $event->registration_deadline;

        if ($deadline && $now->greaterThanOrEqualTo($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đăng ký. Đã qua hạn đóng đăng ký của sự kiện này.'
            ], 400);
        }

        // Cập nhật trạng thái thành cancelled
        $wasApproved = $registration->status === 'approved';
        $freedSeats  = $registration->seats;
        $registration->update(['status' => 'cancelled']);

        // Tự động đẩy người tiếp theo trong danh sách chờ lên nếu có chỗ trống
        if ($wasApproved) {
            $remainingSeats = $freedSeats;
            $pendingQueue   = Registration::where('event_id', $eventId)
                                          ->where('status', 'pending')
                                          ->orderBy('created_at', 'asc')
                                          ->get();

            foreach ($pendingQueue as $nextPending) {
                if ($remainingSeats <= 0) {
                    break;
                }
                if ($nextPending->seats <= $remainingSeats) {
                    $nextPending->update(['status' => 'approved']);
                    $remainingSeats -= $nextPending->seats;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Hủy đăng ký tham gia sự kiện thành công!',
            'data'    => $registration
        ]);
    }
}
