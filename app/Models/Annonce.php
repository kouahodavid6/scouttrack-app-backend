<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Annonce extends Model
{
    protected $fillable = [
        'titre',
        'contenu',
        'type',
        'created_by_id',
        'created_by_type',
        'target_type',
        'target_ids',
        'is_published',
        'published_at',
        'expires_at'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'target_ids' => 'array',
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

    // Relation polymorphique avec le créateur
    public function creator()
    {
        return $this->morphTo('created_by', 'created_by_type', 'created_by_id');
    }

    // Accesseur pour le statut
    public function getStatutAttribute(): string
    {
        $now = Carbon::now();
        
        if (!$this->is_published) {
            return 'brouillon';
        }
        
        if ($this->expires_at && Carbon::parse($this->expires_at) < $now) {
            return 'expire';
        }
        
        if ($this->published_at && Carbon::parse($this->published_at) > $now) {
            return 'programme';
        }
        
        return 'publie';
    }

    // Accesseur pour le label du statut
    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'brouillon' => 'Brouillon',
            'expire' => 'Expiré',
            'programme' => 'Programmé',
            default => 'Publié'
        };
    }

    // Accesseur pour la couleur du statut
    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'brouillon' => 'gray',
            'expire' => 'red',
            'programme' => 'orange',
            default => 'green'
        };
    }
}