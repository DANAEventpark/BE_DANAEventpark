<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public function category()
{
    return $this->belongsTo(Categories::class);
}

public function organizer()
{
    return $this->belongsTo(User::class, 'organizer_id');
}
}
