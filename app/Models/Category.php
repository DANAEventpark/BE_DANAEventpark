<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'image'];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
