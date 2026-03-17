<?php
// app/Models/Jeune.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class Jeune extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'nom',
        'date_naissance',
        'tel',
        'photo',
        'email',
        'password',
        'role',
        'cu_id',
        'branche_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    protected $appends = ['age']; // Pour avoir age comme attribut calculé

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

    // Accesseur pour l'âge (calculé automatiquement depuis date_naissance)
    public function getAgeAttribute()
    {
        return $this->date_naissance ? Carbon::parse($this->date_naissance)->age : null;
    }

    // Relations
    public function cu(): BelongsTo
    {
        return $this->belongsTo(CU::class);
    }

    public function branche(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }
}