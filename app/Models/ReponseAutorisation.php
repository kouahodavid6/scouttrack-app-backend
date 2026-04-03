<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReponseAutorisation extends Model
{
    protected $table = 'reponses_autorisations';
    
    protected $fillable = [
        'demande_id',
        'parent_id',
        'jeune_id',
        'reponse',
        'donnees_formulaire',
        'signature'
    ];

    protected $casts = [
        'donnees_formulaire' => 'array'
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

        // Après la création d'une réponse, vérifier si la demande est complète
        static::created(function ($model) {
            $demande = $model->demande;
            if ($demande) {
                $demande->updateStatusIfComplete();
            }
        });
    }

    public function demande()
    {
        return $this->belongsTo(DemandeAutorisation::class, 'demande_id');
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    public function jeune()
    {
        return $this->belongsTo(Jeune::class, 'jeune_id');
    }
}