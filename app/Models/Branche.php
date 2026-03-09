<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // ← CHANGER ICI
use Illuminate\Support\Str;

class Branche extends Model
{
    protected $fillable = [
        'nomBranche'
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

    /**
     * Une branche a PLUSIEURS étapes
     */
    public function etapes(): HasMany
    {
        return $this->hasMany(Etape::class, 'branche_id');
    }
}