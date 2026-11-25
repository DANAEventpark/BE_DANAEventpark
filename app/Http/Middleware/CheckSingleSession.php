<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckSingleSession
{
    /**
     * Ensure only the most recently issued token for a user remains active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = JWTAuth::getToken();

        if ($user && $token) {
            $tokenHash = hash('sha256', (string) $token);

            if (empty($user->remember_token) || !hash_equals($user->remember_token, $tokenHash)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phiên đăng nhập đã hết hạn do tài khoản được đăng nhập ở nơi khác.',
                ], 401);
            }
        }

        return $next($request);
    }
}
