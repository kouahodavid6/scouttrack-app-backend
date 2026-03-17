<?php
// app/Models/Branche.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Branche extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomBranche',
        'ordreBranche',
        'age_min',
        'age_max'
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

    // Relations
    public function jeunes()
    {
        return $this->hasMany(Jeune::class);
    }

    public function cus()
    {
        return $this->hasMany(CU::class);
    }

    // Vérifier si un âge correspond à la branche
    public function ageEstValide($age)
    {
        return $age >= $this->age_min && $age <= $this->age_max;
    }

    // Vérifier si une date de naissance correspond à la branche
    public function dateNaissanceEstValide($dateNaissance)
    {
        $age = Carbon::parse($dateNaissance)->age;
        return $this->ageEstValide($age);
    }

    // Obtenir la tranche d'âge en texte
    public function getTrancheAgeAttribute()
    {
        return "{$this->age_min} - {$this->age_max} ans";
    }
}