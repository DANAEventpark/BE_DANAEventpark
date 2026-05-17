<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'organization_name' => 'nullable|string|max:150',
            'role' => 'nullable|in:attendee,organizer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $roleName = $request->input('role', 'attendee');
        $role = Role::query()->where('name', $roleName)->first();

        if (! $role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role is invalid.',
            ], 422);
        }

        if ($roleName === 'organizer' && ! $request->filled('organization_name')) {
            return response()->json([
                'status' => 'error',
                'message' => 'organization_name is required for organizer accounts.',
            ], 422);
        }

        $user = User::query()->create([
            'role_id' => $role->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'organization_name' => $request->organization_name,
        ])->load('role:id,name');

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = JWTAuth::user()->load('role:id,name');

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user' => JWTAuth::user()->load('role:id,name'),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user' => JWTAuth::user()->load('role:id,name'),
            'authorization' => [
                'token' => JWTAuth::refresh(),
                'type' => 'bearer',
            ],
        ]);
    }
}