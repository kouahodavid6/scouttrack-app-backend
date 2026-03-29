<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'likeable_id',
        'likeable_type',
        'author_type',
        'author_id',
        'session_id',
    ];

    public function likeable()
    {
        return $this->morphTo();
    }
}