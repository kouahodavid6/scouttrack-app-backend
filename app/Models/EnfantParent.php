<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;  // ← Importer Pivot au lieu de Model
use Illuminate\Support\Str;

class EnfantParent extends Pivot  // ← Étendre Pivot, pas Model
{
    protected $table = 'enfant_parents';
    
    protected $fillable = [
        'jeune_id',
        'parent_id',
        'lien',
        'autorisation_camp',
        'autorisations'
    ];

    protected $casts = [
        'autorisation_camp' => 'boolean',
        'autorisations' => 'array'
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

    public function jeune()
    {
        return $this->belongsTo(Jeune::class, 'jeune_id');
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }
}