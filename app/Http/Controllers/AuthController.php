<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|string|max:15',
            'role'     => 'in:attendee,organizer',
        ]);


        $result = $this->authService->register($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Đăng ký thành công',
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
                'message' => 'Đăng nhập thành công',
                'data'    => [
                    'user'       => $result['user'],
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->errors()['email'][0] ?? 'Email hoặc mật khẩu không đúng',
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Đăng xuất thành công',
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => $request->user(),
            ],
        ], 200);
    }
}
