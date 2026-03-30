<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotisation_id',
        'jeune_id',
        'jeune_nom',
        'jeune_email',
        'transaction_id',
        'montant',
        'numero_telephone',
        'statut',
        'kkiapay_response',
        'date_paiement'
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $casts = [
        'montant' => 'decimal:0',
        'kkiapay_response' => 'array',
        'date_paiement' => 'datetime'
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

    public function cotisation()
    {
        return $this->belongsTo(Cotisation::class);
    }

    public function jeune()
    {
        return $this->belongsTo(Jeune::class, 'jeune_id', 'id');
    }

    public function getMontantFormattedAttribute()
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }
    
    public function getReferenceAttribute()
    {
        return 'SCOT-' . strtoupper(substr($this->id, 0, 8));
    }
}