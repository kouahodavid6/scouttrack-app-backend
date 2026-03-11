<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Reunion extends Model
{
    protected $fillable = [
        'date_reunion',
        'heure_debut',
        'heure_fin',
        'cu_id'
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

    // Relation avec le CU
    public function cu(): BelongsTo
    {
        return $this->belongsTo(CU::class);
    }
}
