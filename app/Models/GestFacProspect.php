<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestFacProspect extends Model
{
    //
    use HasFactory;

    protected $table = 'gest_fac_prospects'; // Nom de la table

    protected $fillable = [
        'nom_societe',
        'nom',
        'email',
        'sexe',
        'telephone',
        'site_web',
        'adresse',
        'ville',
        'pays',
        'numero_siren',
        'type',
        'client',
        'gest_com_entreprise_id',
    ];

    // Relation avec le modèle GestComEntreprise
    public function entreprise()
    {
        return $this->belongsTo(GestComEntreprise::class, 'gest_com_entreprise_id');
    }
}
