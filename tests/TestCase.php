<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set the currently logged in user for the request using JWT.
     *
    * @param  \App\Models\User  $user
     * @return $this
     */
    public function actingAsJwt(User $user)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', 'Bearer ' . $token);

        return $this;
    }

    /**
     * Generate authorization header for a user.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public function jwtHeader(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }
}
