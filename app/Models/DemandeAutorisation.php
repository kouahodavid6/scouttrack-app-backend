<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DemandeAutorisation extends Model
{
    protected $table = 'demandes_autorisations';
    
    protected $fillable = [
        'cu_id',
        'titre',
        'description',
        'date_activite',
        'lieu',
        'texte_trous',
        'status'
    ];

    protected $casts = [
        'date_activite' => 'date',
        'texte_trous' => 'array'
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

    public function cu()
    {
        return $this->belongsTo(CU::class, 'cu_id');
    }

    public function reponses()
    {
        return $this->hasMany(ReponseAutorisation::class, 'demande_id');
    }

    public function getTauxParticipationAttribute()
    {
        $totalReponses = $this->reponses()->count();
        if ($totalReponses === 0) return 0;
        
        $reponsesOui = $this->reponses()->where('reponse', 'oui')->count();
        return round(($reponsesOui / $totalReponses) * 100);
    }

    /**
     * Vérifie si tous les jeunes ont répondu et met à jour le statut
     */
    public function updateStatusIfComplete()
    {
        try {
            // Récupérer le CU associé
            $cu = $this->cu;
            
            if (!$cu) {
                return;
            }
            
            // Compter tous les jeunes du CU
            $totalJeunes = $cu->jeunes()->count();
            
            // Compter toutes les réponses pour cette demande
            $totalReponses = $this->reponses()->count();
            
            // Si tous les jeunes ont répondu et que le statut n'est pas déjà terminé
            if ($totalJeunes > 0 && $totalReponses >= $totalJeunes && $this->status !== 'terminee') {
                $this->status = 'terminee';
                $this->save();
            }
        } catch (\Exception $e) {
            Log::error('Erreur updateStatusIfComplete: ' . $e->getMessage());
        }
    }
}