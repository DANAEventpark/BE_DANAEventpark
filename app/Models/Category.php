<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Khai báo cho HasMany
use App\Models\Event; // Khai báo để Model Category biết Event là ai

class Category extends Model
{
    protected $table = 'categories';

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
    }
}

