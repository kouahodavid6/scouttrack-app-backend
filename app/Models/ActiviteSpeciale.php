<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ActiviteSpeciale extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'type',
        'date_debut',
        'date_fin',
        'heure_debut',
        'heure_fin',
        'lieu',
        'image',
        'cu_id'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
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

    // Relation avec le chef d'unité
    public function cu(): BelongsTo
    {
        return $this->belongsTo(CU::class);
    }

    // Accesseur pour le statut
    public function getStatutAttribute(): string
    {
        $today = Carbon::today();
        $debut = Carbon::parse($this->date_debut);
        $fin = $this->date_fin ? Carbon::parse($this->date_fin) : $debut;

        if ($fin < $today) {
            return 'termine';
        } elseif ($debut <= $today && $fin >= $today) {
            return 'en_cours';
        } else {
            return 'a_venir';
        }
    }

    // Accesseur pour l'affichage de la date
    public function getDateDisplayAttribute(): string
    {
        $debut = Carbon::parse($this->date_debut)->format('d/m/Y');
        if ($this->date_fin && $this->date_fin != $this->date_debut) {
            $fin = Carbon::parse($this->date_fin)->format('d/m/Y');
            return "Du {$debut} au {$fin}";
        }
        return "Le {$debut}";
    }

    // Accesseur pour l'affichage de l'heure
    public function getHeureDisplayAttribute(): string
    {
        $heure = '';
        if ($this->heure_debut) {
            $heure = substr($this->heure_debut, 0, 5);
            if ($this->heure_fin) {
                $heure .= ' - ' . substr($this->heure_fin, 0, 5);
            }
        }
        return $heure;
    }
}