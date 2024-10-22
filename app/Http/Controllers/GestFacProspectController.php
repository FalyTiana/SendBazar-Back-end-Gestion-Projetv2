<?php

namespace App\Http\Controllers;

use App\Models\GestComEntreprise;
use App\Models\GestFacProspect;
use Illuminate\Http\Request;

class GestFacProspectController extends Controller
{
    /**
     * Récupère tous les prospects appartenant à une entreprise donnée via l'ID dans l'URL.
     */
    public function index($gest_com_entreprise_id)
    {
        // Vérifier que l'entreprise existe
        $prospects = GestFacProspect::where('gest_com_entreprise_id', $gest_com_entreprise_id)->get();

        return response()->json($prospects);
    }

    /**
     * Stocker un nouveau prospect dans la base de données.
     */
    // Méthode pour stocker un nouveau prospect
    public function store(Request $request, $gest_com_entreprise_id)
    {
        // Vérifier si l'entreprise existe
        $this->validateEnterpriseExists($gest_com_entreprise_id);

        $validatedData = $request->validate([
            'nom_societe' => 'nullable|string',
            'nom' => 'nullable|string',
            'email' => 'nullable|email',
            'sexe' => 'nullable',
            'telephone' => 'nullable|string',
            'site_web' => 'nullable|string',
            'adresse' => 'nullable|string',
            'ville' => 'nullable|string',
            'pays' => 'nullable|string',
            'numero_siren' => 'nullable|string',
            'type' => 'nullable',
        ]);

        $validatedData['gest_com_entreprise_id'] = $gest_com_entreprise_id; // Ajouter l'ID de l'entreprise

        $prospect = GestFacProspect::create($validatedData);

        return response()->json($prospect, 201);
    }

    /**
     * Afficher un prospect spécifique.
     */
    public function show($id)
    {
        $prospect = GestFacProspect::findOrFail($id);
        return response()->json($prospect);
    }

    /**
     * Mettre à jour un prospect existant.
     */
    public function update(Request $request, $id, $gest_com_entreprise_id)
    {
        // Vérifier si l'entreprise existe
        $this->validateEnterpriseExists($gest_com_entreprise_id);

        $prospect = GestFacProspect::findOrFail($id);

        // Vérifier si le prospect appartient à l'entreprise donnée
        if ($prospect->gest_com_entreprise_id != $gest_com_entreprise_id) {
            return response()->json(['message' => 'Le prospect n\'appartient pas à l\'entreprise.'], 403);
        }

        $validatedData = $request->validate([
            'nom_societe' => 'nullable|string',
            'nom' => 'nullable|string',
            'email' => 'nullable|email',
            'sexe' => 'nullable',
            'telephone' => 'nullable|string',
            'site_web' => 'nullable|string',
            'adresse' => 'nullable|string',
            'ville' => 'nullable|string',
            'pays' => 'nullable|string',
            'numero_siren' => 'nullable|string',
            'type' => 'nullable',
        ]);


        $prospect->update($validatedData);

        return response()->json($prospect);
    }


    /**
     * Supprimer un prospect.
     */
    public function destroy($id)
    {
        $prospect = GestFacProspect::findOrFail($id);
        $prospect->delete();

        return response()->json(['message' => 'Prospect supprimé avec succès.']);
    }

    // Méthode pour valider l'existence de l'entreprise
    private function validateEnterpriseExists($gest_com_entreprise_id)
    {
        if (!GestComEntreprise::where('id', $gest_com_entreprise_id)->exists()) {
            return response()->json(['message' => 'Entreprise non trouvée.'], 404);
        }
    }
}
