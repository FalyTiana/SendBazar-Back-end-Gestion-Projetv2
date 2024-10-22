<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class GestComUtilisateur extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'gest_com_utilisateurs'; // Nom de la table

    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'poste',
        'role',
        'mot_de_passe',
        'photo_profil',
        'gest_com_entreprise_id',
    ];

    protected $hidden = ['mot_de_passe', 'created_at', 'updated_at'];

    public function setMotDePasseAttribute($value)
    // Mutator pour hacher le mot de passe automatiquement
    {
        $this->attributes['mot_de_passe'] = Hash::make($value);
    }

    // Relation avec le modèle GestComEntreprise
    public function entreprise()
    {
        return $this->belongsTo(GestComEntreprise::class, 'gest_com_entreprise_id');
    }

    // Un employé peut être chef de plusieurs projets
    public function projetsCommeChef(): BelongsToMany
    {
        return $this->belongsToMany(GestProjProjet::class, 'chef_projet');
    }

    // Un employé peut être membre de plusieurs projets
    public function projetsCommeMembre(): BelongsToMany
    {
        return $this->belongsToMany(GestProjProjet::class, 'membre_projet');
    }

    // Un utilisateur a plusieurs tâches
    public function taches(): HasMany
    {
        return $this->hasMany(GestProjTache::class);
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(GestProjCommentaire::class, 'gest_com_utilisateur_id');
    }

    // Event for deleting
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($utilisateur) {
            if ($utilisateur->photo_profil && Storage::disk('public')->exists($utilisateur->photo_profil)) {
                Storage::disk('public')->delete($utilisateur->photo_profil);
            }
        });
    }
}
