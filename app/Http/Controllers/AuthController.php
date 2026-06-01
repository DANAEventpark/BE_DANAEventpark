<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use App\Models\User;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email:rfc' . (app()->environment('testing') ? '' : ',dns'), 'max:255', 'unique:users'],
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:15',
            'role'     => 'in:attendee,organizer',
        ]);


        $result = $this->authService->register($data);

        // Fire Registered event to send verification email
        event(new Registered($result['user']));

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.register_success'),
            'data'    => [
                'user'       => $result['user'],
                'token'      => $result['token'],
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->authService->login($data);

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.login_success'),
                'data'    => [
                    'user'       => $result['user'],
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $e) {
            $msg = $e->errors()['email'][0] ?? __('messages.login_failed');
            
            if ($msg === 'email_unverified') {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'EMAIL_UNVERIFIED',
                    'message' => __('messages.email_unverified') ?? 'Vui lòng kiểm tra hộp thư và xác thực email trước khi đăng nhập.',
                ], 403);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $msg,
            ], 401);
        }
    }

    public function googleLogin(Request $request)
    {
        $data = $request->validate([
            'email'     => 'required|string|email',
            'name'      => 'required|string',
            'google_id' => 'required|string',
            'avatar'    => 'nullable|string',
        ]);

        $result = $this->authService->googleLogin($data);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.login_success'),
            'data'    => [
                'user'       => $result['user'],
                'token'      => $result['token'],
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.logout_success'),
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => $request->user()->load('role'),
            ],
        ], 200);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        if (!$user) {
            return redirect($frontendUrl . '/login?error=user_not_found');
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect($frontendUrl . '/login?error=invalid_link');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/login?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect($frontendUrl . '/login?verified=success');
    }

    public function resendVerificationEmail(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã được xác nhận từ trước'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Đã gửi lại email xác nhận. Vui lòng kiểm tra hộp thư.'], 200);
    }
}
