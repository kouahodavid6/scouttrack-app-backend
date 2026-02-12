<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Region extends Model
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
        'nation_id'
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

    // Relation avec la nation
    public function nation(): BelongsTo
    {
        return $this->belongsTo(Nation::class);
    }

    // Relation avec les districts
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}