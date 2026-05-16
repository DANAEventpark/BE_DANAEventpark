<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Categories;
use App\Models\User;
use App\Models\Registration;
use App\Models\Review;

class Event extends Model
{
    protected $table = 'events';


    protected $fillable = [
        'organizer_id',
        'category_id',
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'registration_deadline',
        'capacity',
        'cancel_reason',
        'status',
    ];

    /**
     * CATEGORY
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    /**
     * ORGANIZER
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * REGISTRATIONS
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'event_id');
    }

    /**
     * REVIEWS
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'event_id');
    }

}
