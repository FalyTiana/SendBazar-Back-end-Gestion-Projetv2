<?php

namespace App\Http\Controllers;

use App\Models\GestComUtilisateur;
use App\Models\GestProjCommentaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommentaireController extends Controller
{
    
    // Afficher la liste des commentaires liés à un projet donné
    public function index($projetId)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        // Récupérer les commentaires associés à un projet spécifique
        $commentaires = GestProjCommentaire::with(['utilisateur', 'projet'])
            ->where('gest_proj_projet_id', $projetId)
            ->get();

        return response()->json($commentaires);
    }

    // Afficher un commentaire spécifique
    // public function show($id)
    // {
    //     $commentaire = GestProjCommentaire::with(['utilisateur', 'projet'])->findOrFail($id);
    //     return response()->json($commentaire);
    // }

    // Créer un nouveau commentaire
    public function store(Request $request)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $validated = $request->validate([
            'contenu' => 'required|string',
            'file' => 'nullable|file',
            'gest_com_utilisateur_id' => 'required|exists:gest_com_utilisateurs,id',
            'gest_proj_projet_id' => 'required|exists:gest_proj_projets,id',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('commentaires_files', 'public');
            $validated['file'] = $path;
        }

        $commentaire = GestProjCommentaire::create($validated);

        return response()->json(['message' => 'Commentaire créé avec succès', 'commentaire' => $commentaire]);
    }

    // Mettre à jour un commentaire
    public function update(Request $request, $id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $commentaire = GestProjCommentaire::findOrFail($id);

        $validated = $request->validate([
            'contenu' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,png,pdf,doc,docx',
            'gest_com_utilisateur_id' => 'required|exists:gest_com_utilisateurs,id',
            'gest_proj_projet_id' => 'required|exists:gest_proj_projets,id',
        ]);

        if ($request->hasFile('file')) {
            // Supprimer l'ancien fichier
            if ($commentaire->file) {
                Storage::disk('public')->delete($commentaire->file);
            }

            $path = $request->file('file')->store('commentaires_files', 'public');
            $validated['file'] = $path;
        }

        $commentaire->update($validated);

        return response()->json(['message' => 'Commentaire mis à jour avec succès', 'commentaire' => $commentaire]);
    }

    // Supprimer un commentaire
    public function destroy($id)
    {
        // Récupérer l'utilisateur authentifié 
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
        if (!$user || !($user instanceof GestComUtilisateur)) {
            return response()->json(['error' => 'Utilisateur non autorisé'], 403);
        }

        $commentaire = GestProjCommentaire::findOrFail($id);

        if ($commentaire->file) {
            // Supprimer le fichier lié au commentaire
            Storage::disk('public')->delete($commentaire->file);
        }

        $commentaire->delete();

        return response()->json(['message' => 'Commentaire supprimé avec succès']);
    }
}
