<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reunion extends Model
{
    protected $table = 'reunions';
    
    protected $fillable = [
        'date_reunion',
        'heure_debut',
        'heure_fin',
        'is_presented',
        'cu_id'
    ];

    protected $casts = [
        'date_reunion' => 'date',
        'is_presented' => 'boolean',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation avec le CU (Chef d'Unité)
     */
    public function cu(): BelongsTo
    {
        return $this->belongsTo(CU::class, 'cu_id');
    }

    /**
     * Relation avec les présences
     */
    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class, 'reunion_id');
    }
}