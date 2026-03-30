<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cotisation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'montant',
        'type',
        'created_by_type',
        'created_by_id',
        'date_limite',
        'statut'
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $casts = [
        'montant' => 'decimal:0',
        'date_limite' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function createur()
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function getMontantFormattedAttribute()
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }
}