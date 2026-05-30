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
            return response()->json([
                'status'  => 'error',
                'message' => $e->errors()['email'][0] ?? __('messages.login_failed'),
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
}
