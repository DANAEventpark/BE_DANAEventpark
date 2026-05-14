<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categories extends Model
{
    protected $table = 'categories';

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
    }
}
