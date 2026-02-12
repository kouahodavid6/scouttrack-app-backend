<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class Jeune extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'nom',
        'age',
        'niveau',
        'tel',
        'photo',
        'email',
        'password',
        'role',
        'cu_id'
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

    // Relation avec le CU
    public function cu(): BelongsTo
    {
        return $this->belongsTo(CU::class);
    }
}