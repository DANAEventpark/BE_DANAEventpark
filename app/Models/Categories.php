<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categories extends Model
{
    protected $table = 'categories';

    public $timestamps = false;

    protected $guarded = [];

    protected $appends = [
        'image_url',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $this->image) === 1) {
            return $this->image;
        }

        if (str_contains($this->image, '/')) {
            return asset(ltrim($this->image, '/'));
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}