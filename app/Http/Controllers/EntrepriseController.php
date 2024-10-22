<?php

namespace App\Http\Controllers;

use App\Models\GestComEntreprise;
use App\Models\GestComUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EntrepriseController extends Controller
{
    //
    public function getAllEntreprises()
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier si l'utilisateur a le rôle adminSuper
            if ($user->role != "adminSuper") {
                return response()->json(['error' => 'Vous n\'êtes pas un admin super'], 403);
            }

            // Récupérer les entreprises avec leurs administrateurs
            $entreprises = GestComEntreprise::with('administrateurs')->get();

            // Filtrer les entreprises qui ont des administrateurs
            $entreprises = $entreprises->filter(function ($entreprise) {
                return $entreprise->administrateurs->isNotEmpty();
            });

            // Retourner les entreprises et leurs administrateurs dans une réponse JSON
            return response()->json([
                'message' => 'Liste des entreprises récupérée avec succès',
                'entreprises' => $entreprises
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la récupération des entreprises.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateEntreprise(Request $request, $id)
    {
        try {
            // Récupérer l'utilisateur authentifié 
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier si l'utilisateur a le rôle admin
            if ($user->role != "admin") {
                return response()->json(['error' => 'Vous n\'êtes pas un admin'], 403);
            }

            // Trouver l'entreprise via l'ID passé dans l'URL
            $entreprise = GestComEntreprise::findOrFail($id);

            // Vérifier que l'administrateur est bien lié à cette entreprise
            if ($entreprise->id != $user->gest_com_entreprise_id) {
                return response()->json(['message' => 'Vous n\'êtes pas autorisé à modifier cette entreprise'], 403);
            }

            // Validation des données de l'entreprise
            $request->validate([
                'nom' => 'nullable|string|max:255',
                // Ajouter d'autres champs à valider si nécessaire
            ]);

            // Mise à jour des informations de l'entreprise
            if ($request->has('nom')) {
                $entreprise->nom = $request->nom;
            }

            // Sauvegarder les changements
            $entreprise->save();

            // Retourner une réponse
            return response()->json([
                'message' => 'Informations de l\'entreprise mises à jour avec succès',
                'entreprise' => $entreprise
            ]);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la modification de l\'entreprise.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de la modification de l\'entreprise.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteEntrepriseById($id)
    {
        try {
            // Récupérer l'utilisateur authentifié 
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier si l'utilisateur a le rôle admin
            if ($user->role != "adminSuper") {
                return response()->json(['error' => 'Vous n\'êtes pas un admin super'], 403);
            }

            // Trouver l'entreprise via l'ID passé dans l'URL
            $entreprise = GestComEntreprise::findOrFail($id);

            // Supprimer l'entreprise
            $entreprise->delete();

            // Retourner une réponse de succès
            return response()->json([
                'message' => 'Entreprise et administrateurs supprimés avec succès'
            ], 200); // Retourner une réponse 200 en cas de succès
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la suppression de l\'entreprise.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
