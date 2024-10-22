<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestProjTache extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'gest_proj_projet_id',
        'gest_com_utilisateur_id',
    ];

    // Une tâche appartient à un projet
    public function projet(): BelongsTo
    {
        return $this->belongsTo(GestProjProjet::class);
    }

    // Une tâche appartient à un utilisateur
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(GestComUtilisateur::class, 'gest_com_utilisateur_id');
    }

    public function champs()
    {
        return $this->morphMany(GestProjChamp::class, 'champable');
    }
}
