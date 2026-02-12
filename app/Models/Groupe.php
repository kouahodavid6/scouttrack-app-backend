<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Groupe extends Model
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
        'district_id'
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

    // Relation avec le district
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    // Relation avec les CUs
    public function cus(): HasMany
    {
        return $this->hasMany(CU::class);
    }
}