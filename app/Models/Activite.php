<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Activite extends Model
{
    protected $fillable = [
        'nom_act',
        'description',
        'date_debut',
        'date_fin',
        'badge',
        'etape_id'
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

    public function etape(): BelongsTo
    {
        return $this->belongsTo(Etape::class);
    }
}
