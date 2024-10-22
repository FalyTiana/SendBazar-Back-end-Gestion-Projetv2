<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class GestProjChamp extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'valeur', 'champable_id', 'champable_type', 'type'];

    public function champable(): MorphTo
    {
        return $this->morphTo();
    }

    // Si c'est un fichier, renvoyer l'URL complète du fichier
    public function getValeurAttribute($valeur)
    {
        if ($this->type === 'file') {
            return asset('storage/' . $valeur); // Retourne l'URL complète du fichier
        }

        return $valeur;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($champ) {
            // Supprimer un fichier si nécessaire
            if ($champ->type === 'file' && $champ->valeur && Storage::exists('public/' . $champ->valeur)) {
                Storage::delete('public/' . $champ->valeur);
            }
        });
    }
}
