<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Danh sách exception không cần report (ghi log).
     */
    protected $dontReport = [];

    /**
     * Danh sách input nhạy cảm không log.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render the exception — luôn trả JSON cho API, không bao giờ redirect.
     * Fix BUG-014: /api/auth/me trả 500 khi Accept: text/html
     * Fix BUG-016: ModelNotFoundException trả lỗi raw thay vì 404 JSON
     */
    public function render($request, Throwable $e)
    {
        // Tất cả route /api/* luôn trả JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            // BUG-016: findOrFail() / firstOrFail() → 404 JSON thân thiện
            if ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());
                $messages = [
                    'Event'        => 'Sự kiện không tồn tại hoặc đã bị xóa.',
                    'User'         => 'Người dùng không tồn tại.',
                    'Registration' => 'Đăng ký không tồn tại.',
                    'Review'       => 'Đánh giá không tồn tại.',
                ];
                return response()->json([
                    'success' => false,
                    'message' => $messages[$model] ?? 'Không tìm thấy dữ liệu.',
                ], 404);
            }

            // BUG-014: Unauthenticated → 401 JSON (không redirect về route 'login')
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập. Vui lòng đăng nhập để tiếp tục.',
                ], 401);
            }

            // Validation errors → 422 JSON
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // HTTP exceptions (403, 404, v.v.)
            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Lỗi HTTP ' . $e->getStatusCode(),
                ], $e->getStatusCode());
            }

            // BUG-002: Tránh lộ SQL / stack trace — trả JSON lỗi chung
            if (config('app.env') !== 'local') {
                return response()->json([
                    'success' => false,
                    'message' => 'Đã có lỗi xảy ra phía server. Vui lòng thử lại sau.',
                ], 500);
            }
        }

        return parent::render($request, $e);
    }
}
