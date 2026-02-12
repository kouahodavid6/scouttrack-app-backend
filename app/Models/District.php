<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class District extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'nom',
        'niveau',
        'tel',
        'photo',
        'email',
        'password',
        'role',
        'region_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    // Relation avec la région
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    // Relation avec les groupes
    public function groupes(): HasMany
    {
        return $this->hasMany(Groupe::class);
    }
}