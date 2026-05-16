<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * User Model
 *
 * Implements JWTSubject để tích hợp với tymon/jwt-auth.
 * Mỗi user có role: 'attendee' hoặc 'organizer'.
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Các trường có thể mass-assign
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',        // 'attendee' | 'organizer'
        'avatar',
    ];

    /**
     * Các trường ẩn khỏi JSON response
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── JWT Interface ─────────────────────────────────────────────────────

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
        ];
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
