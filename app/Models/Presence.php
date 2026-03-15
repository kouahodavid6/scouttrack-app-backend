<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Presence extends Model
{
    protected $fillable = [
        'is_present',
        'jeune_id',
        'reunion_id'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation avec jeune
    public function jeune(): BelongsTo
    {
        return $this->belongsTo(Jeune::class);
    }

    // Relation avec reunion
    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class);
    }
}
