<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_type',
        'author_id',
        'author_name',
        'context',
        'message',
        'photo_url',
        'video_url',
        'audio_url'
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    protected $appends = ['author_details'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation polymorphique avec les différents types d'utilisateurs
    public function author()
    {
        return $this->morphTo('author', 'author_type', 'author_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id')->orderBy('created_at', 'asc');
    }

    // Vérifier si l'utilisateur est l'auteur
    public function isAuthor($userType, $userId)
    {
        return $this->author_type === $userType && $this->author_id === $userId;
    }
    
    // Accesseur pour obtenir les détails de l'auteur
    public function getAuthorDetailsAttribute()
    {
        // Pour les visiteurs (forum public)
        if ($this->author_type === 'visitor') {
            return [
                'name' => $this->author_name ?? 'Anonyme',
                'type' => 'Visiteur',
                'type_label' => 'Visiteur',
                'photo' => null,
                'role' => 'visitor'
            ];
        }
        
        // Pour les utilisateurs authentifiés
        $modelClass = $this->getModelClassForType($this->author_type);
        if ($modelClass && $this->author_id) {
            $author = $modelClass::find($this->author_id);
            if ($author) {
                return [
                    'name' => $author->nom ?? $author->name ?? $this->author_name ?? 'Membre',
                    'type' => $this->getTypeLabel($this->author_type),
                    'type_label' => $this->getTypeLabel($this->author_type),
                    'photo' => $author->photo ?? null,
                    'role' => $this->author_type
                ];
            }
        }
        
        return [
            'name' => $this->author_name ?? 'Membre',
            'type' => $this->getTypeLabel($this->author_type),
            'type_label' => $this->getTypeLabel($this->author_type),
            'photo' => null,
            'role' => $this->author_type
        ];
    }
    
    private function getModelClassForType($type)
    {
        $models = [
            'nation' => \App\Models\Nation::class,
            'region' => \App\Models\Region::class,
            'district' => \App\Models\District::class,
            'groupe' => \App\Models\Groupe::class,
            'cu' => \App\Models\CU::class,
            'jeune' => \App\Models\Jeune::class,
        ];
        
        return $models[$type] ?? null;
    }
    
    private function getTypeLabel($type)
    {
        $labels = [
            'nation' => 'Nation',
            'region' => 'Région',
            'district' => 'District',
            'groupe' => 'Groupe',
            'cu' => 'Chef d\'unité',
            'jeune' => 'Jeune',
            'visitor' => 'Visiteur'
        ];
        
        return $labels[$type] ?? ucfirst($type);
    }
}