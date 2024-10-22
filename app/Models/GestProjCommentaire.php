<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GestProjCommentaire extends Model
{
    use HasFactory;

    protected $fillable = ['contenu', 'gest_com_utilisateur_id', 'gest_proj_projet_id', 'file', 'date'];

    // Relation avec l'utilisateur (l'auteur du commentaire)
    public function utilisateur()
    {
        return $this->belongsTo(GestComUtilisateur::class, 'gest_com_utilisateur_id');
    }

    // Relation avec le projet
    public function projet()
    {
        return $this->belongsTo(GestProjProjet::class, 'gest_proj_projet_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($commentaire) {
            if ($commentaire->file) {
                // Supprimer le fichier lié au commentaire
                Storage::disk('public')->delete($commentaire->file);
            }
        });
    }
}
