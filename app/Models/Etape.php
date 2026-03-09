<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // ← AJOUTER
use Illuminate\Support\Str;

class Etape extends Model
{
    protected $fillable = [
        'nom',
        'branche_id'
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
     * Une étape appartient à une branche
     */
    public function branche(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }

    /**
     * Une étape a PLUSIEURS activités
     */
    public function activites(): HasMany // ← AJOUTER CETTE RELATION
    {
        return $this->hasMany(Activite::class, 'etape_id');
    }
}