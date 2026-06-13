<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\Events\Registered;

class AuthService
{
    /**
     * Handle user registration.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $roleName = $data['role'] ?? 'attendee';
        $roleId = $roleName === 'organizer' ? 2 : 1;

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $roleId,
            'phone'    => $data['phone'] ?? null,
        ]);

        $user->load('role');

        event(new Registered($user));

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Handle user login.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $user = User::with('role')->where('email', $data['email'])->first();

        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['email_unverified'],
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Handle user google login.
     *
     * @param array $data
     * @return array
     */
    public function googleLogin(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $roleName = $data['role'] ?? 'attendee';
            $roleId = $roleName === 'organizer' ? 2 : 1;

            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'google_id' => $data['google_id'],
                'role_id'   => $roleId, // Use requested role
                'password'  => null,
            ]);
            $user->markEmailAsVerified();
        } elseif (!$user->google_id) {
            $user->update(['google_id' => $data['google_id']]);
        }

        $user->load('role');

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Handle user logout.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
