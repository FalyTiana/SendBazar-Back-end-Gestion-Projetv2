<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestComEntreprise extends Model
{
    use HasFactory;

    protected $table = 'gest_com_entreprises'; // Nom de la table

    protected $fillable = [
        'nom', // Champs que tu souhaites remplir en masse
    ];

    // Relation avec le modèle GestComUtilisateur
    public function utilisateurs()
    {
        return $this->hasMany(GestComUtilisateur::class, 'gest_com_entreprise_id');
    }

    public function administrateurs()
    {
        return $this->hasMany(GestComUtilisateur::class, 'gest_com_entreprise_id')->where('role', 'admin');
    }

    
    public function prospect()
    {
        return $this->hasMany(GestFacProspect::class, 'gest_com_entreprise_id');
    }
}
