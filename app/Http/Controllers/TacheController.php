<?php

namespace App\Http\Controllers;

use App\Models\GestComUtilisateur;
use App\Models\GestProjProjet;
use App\Models\GestProjTache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TacheController extends Controller
{
    /**
     * Affiche toutes les tâches d'un projet.
     */
    public function index($projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que le projet existe
            $projet = GestProjProjet::findOrFail($projet_id);

            // Vérifier si l'utilisateur est chef ou membre du projet
            $estMembre = $projet->membres()->where('gest_com_utilisateur_id', $user->id)->exists();
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();


            if (!$estMembre && !$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Récupérer toutes les tâches du projet avec l'employé assigné
            $taches = GestProjTache::where('gest_proj_projet_id', $projet_id)
                ->with('utilisateur')  // Assurez-vous que 'utilisateur' correspond au nom de la relation dans le modèle
                ->get();

            return response()->json($taches);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors d\'affiche toutes les tâches d\'un projet',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crée une nouvelle tâche pour un projet et l'assigne à un employé (membre ou chef).
     */
    public function store(Request $request, $projet_id)
    {

        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'nullable|string',
                'date_fin' => 'nullable|string',
                'employe_id' => 'nullable|exists:gest_com_utilisateurs,id',
            ]);

            // Trouver le projet
            $projet = GestProjProjet::findOrFail($projet_id);

            // Vérifier si l'utilisateur est chef du projet
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();


            if (!$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Vérifier si l'employé est bien un membre ou un chef du projet
            $employe = GestComUtilisateur::findOrFail($request->employe_id);

            $isMembre = ($projet->membres()->where('gest_com_utilisateur_id', $employe->id)->exists());
            $isChef =  ($projet->chefs()->where('gest_com_utilisateur_id', $employe->id)->exists());
            if (!$isMembre && !$isChef) {
                return response()->json(['error' => 'Cet employé n\'est ni un membre, ni un chef du projet'], 400);
            }

            // Créer la tâche
            $tache = new GestProjTache($request->only(['titre', 'description', 'date_debut', 'date_fin']));
            $tache->gest_proj_projet_id = $projet->id;

            // Si un employé est assigné, lui attribuer la tâche
            if ($request->filled('employe_id')) {
                $tache->gest_com_utilisateur_id = $request->employe_id;
            }

            $tache->save();

            return response()->json(['message' => 'Tâche créée avec succès', 'tache' => $tache], 201);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de création une nouvelle tâche pour un projet et \'assigne à un employé (membre ou chef).',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche une tâche spécifique.
     */
    public function show($projet_id, $id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que le projet existe
            $projet = GestProjProjet::findOrFail($projet_id);

            // Vérifier si l'utilisateur est chef ou membre du projet
            $estMembre = $projet->membres()->where('gest_com_utilisateur_id', $user->id)->exists();
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();

            if (!$estMembre && !$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Vérifier si la tâche appartient bien au projet donné
            $tache = GestProjTache::where('gest_proj_projet_id', $projet_id)
                ->with('utilisateur')
                ->findOrFail($id);

            return response()->json($tache);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors d\'affiche une tâche spécifique.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour une tâche existante.
     */
    public function update(Request $request, $projet_id, $id)
    {

        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Valider les données de la requête
            $request->validate([
                'titre' => 'string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'nullable|string',
                'date_fin' => 'nullable|string',
                'employe_id' => 'nullable|exists:gest_com_utilisateurs,id'
            ]);

            // Vérifier que le projet existe
            $projet = GestProjProjet::findOrFail($projet_id);


            // Vérifier si l'utilisateur est chef du projet
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();


            if (!$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Vérifier que la tâche appartient bien à ce projet
            $tache = GestProjTache::where('gest_proj_projet_id', $projet_id)->findOrFail($id);

            // Si un changement d'employé est demandé
            if ($request->has('employe_id')) {
                $employe = GestComUtilisateur::findOrFail($request->employe_id);

                // Vérifier que l'employé appartient au projet
                $isMembre = ($projet->membres()->where('gest_com_utilisateur_id', $employe->id)->exists());
                $isChef =  ($projet->chefs()->where('gest_com_utilisateur_id', $employe->id)->exists());
                if (!$isMembre && !$isChef) {
                    return response()->json(['error' => 'Cet employé n\'est ni un membre, ni un chef du projet'], 400);
                }

                // Associer l'employé à la tâche
                $tache->gest_com_utilisateur_id = $request->employe_id ?? "";
                $tache->save();
            }

            // Mettre à jour les autres informations de la tâche
            $tache->update($request->only(['titre', 'description', 'date_debut', 'date_fin']));

            return response()->json(['message' => 'Tâche mise à jour avec succès', 'tache' => $tache]);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de mise à jour une tâche existante.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime une tâche.
     */
    public function destroy($projet_id, $id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que le projet existe
            $projet = GestProjProjet::findOrFail($projet_id);
            // Vérifier si l'utilisateur est chef du projet
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();


            if (!$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Vérifier que la tâche appartient bien à ce projet
            $tache = GestProjTache::where('gest_proj_projet_id', $projet_id)->findOrFail($id);

            // Supprimer la tâche
            $tache->delete();

            return response()->json(['message' => 'Tâche supprimée avec succès']);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors Supprime une tâche',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTachesParProjetEtAssignable($projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que le projet existe
            $projet = GestProjProjet::findOrFail($projet_id);


            // Vérifier si l'utilisateur est chef ou membre du projet
            $estMembre = $projet->membres()->where('gest_com_utilisateur_id', $user->id)->exists();
            $estChef = $projet->chefs()->where('gest_com_utilisateur_id', $user->id)->exists();

            if (!$estMembre && !$estChef) {
                return response()->json(['error' => 'Vous n\'avez pas l\'autorisation de voir les tâches de ce projet'], 403);
            }

            // Récupérer toutes les tâches du projet avec l'employé assigné
            $taches = GestProjTache::where('gest_proj_projet_id', $projet_id)
                ->whereHas('utilisateur', function ($query) use ($user) {
                    $query->where('gest_com_utilisateur_id', $user->id);
                })
                ->with('utilisateur')
                ->get();

            return response()->json($taches);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors d\'affiche toutes les tâches d\'un projet',
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
