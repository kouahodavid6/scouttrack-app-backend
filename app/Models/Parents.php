<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Parents extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'parents';
    
    protected $fillable = [
        'nom',
        'email',
        'tel',
        'photo',
        'password',
        'role',
        'is_active'
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

    public function enfants()
    {
        return $this->belongsToMany(Jeune::class, 'enfant_parents', 'parent_id', 'jeune_id')
            ->using(EnfantParent::class)
            ->withPivot(['id', 'lien', 'autorisation_camp', 'autorisations'])
            ->withTimestamps();
    }

    public function hasEnfant($jeuneId)
    {
        return $this->enfants()->where('jeune_id', $jeuneId)->exists();
    }

    public function reponsesAutorisations()
    {
        return $this->hasMany(ReponseAutorisation::class, 'parent_id');
    }
}