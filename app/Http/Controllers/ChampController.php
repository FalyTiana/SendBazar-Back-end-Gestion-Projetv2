<?php

namespace App\Http\Controllers;

use App\Models\GestComUtilisateur;
use App\Models\GestProjChamp;
use App\Models\GestProjProjet;
use App\Models\GestProjTache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChampController extends Controller
{
    // Créer un champ pour un projet ou une tâche
    public function store(Request $request, $type, $id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $request->validate([
            'label' => 'nullable|string',
            'valeur' => 'nullable|string',
            'type' => 'nullable|string',
            'file' => 'nullable|file', // Le fichier est optionnel, limité à 2 Mo
        ]);

        if ($type === 'projet') {
            $entity = GestProjProjet::findOrFail($id);
        } elseif ($type === 'tache') {
            $entity = GestProjTache::findOrFail($id);
        } else {
            return response()->json(['error' => 'Type non valide'], 400);
        }

        $champData = [
            'label' => $request->input('label'),
            'type' => $request->input('type'),
            'valeur' => $request->input('valeur'),
        ];

        // Si un fichier est uploadé
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('champs', 'public'); // Stocker le fichier dans le répertoire public/storage/champs
            $champData['valeur'] = $filePath;
            $champData['type'] = 'file';
        }

        $champ = $entity->champs()->create($champData);

        return response()->json($champ, 201);
    }


    public function update(Request $request, $id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $request->validate([
            'label' => 'nullable|string',
            'valeur' => 'nullable|string',
            'type' => 'nullable|string',
            'file' => 'nullable|file',
        ]);

        $champ = GestProjChamp::findOrFail($id);

        $champData = [
            'label' => $request->input('label'),
            'type' => $request->input('type'),
            'valeur' => $request->input('valeur'),
        ];

        // Si un nouveau fichier est uploadé
        if ($request->hasFile('file')) {
            // Supprimer l'ancien fichier si nécessaire
            if ($champ->type === 'file' && $champ->valeur) {
                Storage::delete('public/' . $champ->valeur);
            }

            $filePath = $request->file('file')->store('champs', 'public');
            $champData['valeur'] = $filePath;
            $champData['type'] = 'file';
        }

        // Mettre à jour le champ
        $champ->update($champData);

        return response()->json($champ, 200);
    }


    // Supprimer un champ
    public function destroy($id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $champ = GestProjChamp::findOrFail($id);

        // Supprimer un fichier si nécessaire
        if ($champ->type === 'file' && $champ->valeur && Storage::exists('public/' . $champ->valeur)) {
            Storage::delete('public/' . $champ->valeur);
        }

        // Supprimer le champ
        $champ->delete();

        return response()->json(['message' => 'Champ supprimé avec succès'], 200);
    }


    // Récupérer tous les champs pour un projet ou une tâche
    public function index($type, $id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        if ($type === 'projet') {
            $entity = GestProjProjet::findOrFail($id);
        } elseif ($type === 'tache') {
            $entity = GestProjTache::findOrFail($id);
        } else {
            return response()->json(['error' => 'Type non valide'], 400);
        }

        // Récupérer les champs associés
        $champs = $entity->champs;

        return response()->json($champs, 200);
    }
}
