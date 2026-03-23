<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class CU extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'c_u_s';
    
    protected $fillable = [
        'nom',
        'tel',
        'photo',
        'email',
        'password',
        'role',
        'groupe_id',
        'branche_id'
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
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation avec le groupe
    public function groupe(): BelongsTo
    {
        return $this->belongsTo(Groupe::class);
    }

    // Relation avec branche
    public function branche(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }

    public function reunions()
    {
        return $this->hasMany(Reunion::class, 'cu_id');
    }

    public function jeunes()
    {
        return $this->hasMany(Jeune::class, 'cu_id');
    }
}