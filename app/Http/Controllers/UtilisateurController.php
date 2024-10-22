<?php

namespace App\Http\Controllers;

use App\Models\GestComEntreprise;
use App\Models\GestComUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class UtilisateurController extends Controller
{
    public function getUsersByEntreprise($entrepriseId)
    {
        try {
            // On s'assure que l'utilisateur est authentifié via Sanctum
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Trouver l'entreprise via l'ID passé
            $entreprise = GestComEntreprise::findOrFail($entrepriseId);

            // Vérification que l'utilisateur appartient bien à l'entreprise
            if ($user->gest_com_entreprise_id != $entrepriseId) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à accéder à la liste des employés pour cette entreprise'], 403);
            }

            // Utiliser la relation 'utilisateurs' pour récupérer tous les utilisateurs de cette entreprise
            $utilisateurs = $entreprise->utilisateurs;

            // Vérifier si l'entreprise a des utilisateurs
            if ($utilisateurs->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun utilisateur trouvé pour cette entreprise.'
                ], 404);
            }

            // Retourner les utilisateurs dans une réponse JSON
            return response()->json([
                'message' => 'Utilisateurs récupérés avec succès',
                'utilisateurs' => $utilisateurs
            ], 200); // Code 200 pour succès
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la récupération des utilisateurs de l\'entreprise.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteEmployeById($employeId)
    {
        try {
            // On s'assure que l'utilisateur est authentifié via Sanctum
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Vérifier que l'utilisateur a le rôle admin
            if ($user->role != 'admin') {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à supprimer un employé'], 403);
            }

            // Trouver l'employé via l'ID passé
            $employe = GestComUtilisateur::findOrFail($employeId);

            // Vérifier que l'utilisateur appartient à l'entreprise de l'employé à supprimer
            if ($user->gest_com_entreprise_id != $employe->gest_com_entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à supprimer cet employé'], 403);
            }

            // Vérifier si l'employé à supprimer a le rôle admin
            if ($employe->role == 'admin') {
                return response()->json(['error' => 'Vous ne pouvez pas supprimer un employé avec le rôle admin'], 403);
            }

            // Supprimer la photo de profil si elle existe
            if ($employe->photo_profil && Storage::disk('public')->exists($employe->photo_profil)) {
                Storage::disk('public')->delete($employe->photo_profil);
            }

            // Supprimer l'employé
            $employe->delete();

            // Retourner une réponse de succès
            return response()->json([
                'message' => 'Employé supprimé avec succès'
            ], 200); // Code 200 pour succès
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la suppression de l\'employé.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            // On s'assure que l'utilisateur est authentifié via Sanctum
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien authentifié et est de type GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non authentifié ou type incorrect'], 401);
            }

            // Validation des données du profil
            $request->validate([
                'nom' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:gest_com_utilisateurs,email,' . $user->id,
                'telephone' => 'nullable|string|max:15',
                'poste' => 'nullable|string|max:255',
                'photo_profil' => 'nullable|image',
            ]);

            // Mise à jour des informations du profil
            if ($request->has('nom')) {
                $user->nom = $request->nom;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('telephone')) {
                $user->telephone = $request->telephone;
            }
            if ($request->has('poste')) {
                $user->poste = $request->poste;
            }

            // Gestion de l'upload de la photo de profil
            if ($request->hasFile('photo_profil')) {
                // Supprimer l'ancienne photo si elle existe
                if ($user->photo_profil && Storage::disk('public')->exists($user->photo_profil)) {
                    Storage::disk('public')->delete($user->photo_profil);
                }

                // Stocker la nouvelle photo
                $path = $request->file('photo_profil')->store('photos_profil', 'public');
                $user->photo_profil = $path; // Mettre à jour le chemin de la photo de profil
            }

            // Sauvegarder les changements
            $user->save();

            // Retourner une réponse
            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'administrateur' => $user
            ], 200); // Code 200 pour succès
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la mise à jour du profil.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            // On s'assure que l'utilisateur est authentifié via Sanctum
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien authentifié et est de type GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non authentifié ou type incorrect'], 401);
            }

            // Validation des champs
            $request->validate([
                'ancien_mot_de_passe' => 'required|string',
                'nouveau_mot_de_passe' => 'required|string',
            ]);

            // Vérifier que l'ancien mot de passe est correct
            if (!Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe)) {
                return response()->json(['message' => 'Ancien mot de passe incorrect'], 401);
            }

            // Mettre à jour avec le nouveau mot de passe
            $user->mot_de_passe = $request->nouveau_mot_de_passe;

            // Sauvegarder les changements
            $user->save();

            // Retourner une réponse
            return response()->json(['message' => 'Mot de passe mis à jour avec succès'], 200); // Code 200 pour succès
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la mise à jour du profil mot de passe mis à jour.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEntreprise()
    {
        try {
            // Récupérer l'administrateur authentifié
            $user = Auth::user();

            // Vérifier si l'administrateur est bien lié à une entreprise
            if (!$user->entreprise) {
                return response()->json(['error' => 'Aucune entreprise associée à cet administrateur'], 404);
            }

            // Retourner les informations de l'entreprise
            return response()->json([
                'message' => 'Informations de l\'entreprise récupérées avec succès',
                'entreprise' => $user->entreprise
            ]);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de récuperation de l\'informations de l\'entreprise.',
                ['error' => $e->getMessage()]
            );

            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProfile()
    {
        try {
            // Récupérer l'administrateur authentifié
            $user = Auth::user();

            // Retourner les informations de l'administrateur
            return response()->json([
                'message' => 'Informations de l\'administrateur récupérées avec succès',
                'administrateur' => $user
            ]);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de récuperation de l\'informations de l\'administrateur',
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
