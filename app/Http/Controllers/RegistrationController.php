<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function store(Request $request, $eventId)
    {
        $user  = Auth::user();
        $event = Event::findOrFail($eventId);

        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện hiện không mở đăng ký.'
            ], 400);
        }

        if ($event->registration_deadline && now()->greaterThan($event->registration_deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Đã hết hạn đăng ký cho sự kiện này.'
            ], 400);
        }

        // Lấy số ghế muốn đăng ký (mặc định 1)
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
                                ->whereIn('status', ['approved', 'pending'])
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
        $conflictingRegistration = Registration::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereHas('event', function ($q) use ($event) {
                $q->where('id', '!=', $event->id)
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

        // ─── Ràng buộc 3: Số ghế không được vượt quá capacity ───────────────
        if ($seats > $event->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Số ghế đăng ký (' . $seats . ') không được vượt quá giới hạn của sự kiện (' . $event->capacity . ' ghế).'
            ], 422);
        }

        // ─── Xác định trạng thái dựa trên chỗ còn trống ─────────────────────
        $registration = DB::transaction(function () use ($eventId, $user, $event, $seats, &$status, &$message, $request) {
            $approvedSeats = Registration::where('event_id', $eventId)
                                           ->where('status', 'approved')
                                           ->lockForUpdate()
                                           ->sum('seats');
            $hasWaitlist = Registration::where('event_id', $eventId)
                                       ->where('status', 'pending')
                                       ->exists();

            $availableSeats = $event->capacity - $approvedSeats;
            $status = (!$hasWaitlist && $seats <= $availableSeats) ? 'approved' : 'pending';
            $message = $status === 'approved'
                ? __('messages.register_event_success')
                : __('messages.register_event_waitlist');

            $cancelledReg = Registration::where('event_id', $eventId)
                ->where('user_id', $user->id)
                ->where('status', 'cancelled')
                ->first();

            $orgName = $request->input('organization_name');

            if ($cancelledReg) {
                $cancelledReg->update([
                    'status'            => $status,
                    'seats'             => $seats,
                    'organization_name' => $orgName,
                ]);
                return $cancelledReg->fresh();
            }

            return Registration::create([
                'event_id'          => $eventId,
                'user_id'           => $user->id,
                'status'            => $status,
                'seats'             => $seats,
                'organization_name' => $orgName,
            ]);
        });

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

        // Kiểm tra thời hạn hủy
        $now      = now();
        $deadline = $event->registration_deadline;

        if ($deadline && $now->greaterThanOrEqualTo($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đăng ký. Đã qua hạn đóng đăng ký của sự kiện này.'
            ], 400);
        }

        $wasApproved = $registration->status === 'approved';
        $freedSeats  = $registration->seats;

        // Dùng transaction để tránh race condition overbooking
        DB::transaction(function () use ($registration, $wasApproved, $freedSeats, $eventId) {
            $registration->update(['status' => 'cancelled']);

            if ($wasApproved) {
                $remainingSeats = $freedSeats;
                $pendingQueue   = Registration::where('event_id', $eventId)
                                              ->where('status', 'pending')
                                              ->orderBy('created_at', 'asc')
                                              ->get();

                foreach ($pendingQueue as $nextPending) {
                    if ($remainingSeats <= 0) break;
                    if ($nextPending->seats <= $remainingSeats) {
                        $nextPending->update(['status' => 'approved']);
                        $remainingSeats -= $nextPending->seats;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Hủy đăng ký tham gia sự kiện thành công!',
            'data'    => $registration->fresh()
        ]);
    }
}
