<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = false;
    protected $fillable = ['name', 'image'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
    }
}
