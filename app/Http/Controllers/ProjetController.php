<?php

namespace App\Http\Controllers;

use App\Models\GestComUtilisateur;
use App\Models\GestProjProjet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjetController extends Controller
{
    public function creerProjet(Request $request)
    {
        try { 
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Validation des données envoyées
            $request->validate([
                'titre' => 'required|string',
                'entreprise_id' => 'required|exists:gest_com_entreprises,id',
                'chefs' => 'required|array|min:0|max:2', // Doit avoir entre 1 et 3 chefs
                'chefs.*' => 'exists:gest_com_utilisateurs,id', // Vérifie que chaque chef est un employé existant
                'membres' => 'nullable|array',
                'membres.*' => 'exists:gest_com_utilisateurs,id', // Vérifie que chaque membre est un employé existant
            ]);

            // Vérification que l'utilisateur est appartien à l'entreprise
            if ($user->gest_com_entreprise_id != $request->entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à crée de(s) projet pour cette entreprise'], 403);
            }

            // Vérifier si tous les chefs sont bien des employés de l'entreprise
            $chefs = GestComUtilisateur::whereIn('id', $request->chefs)
                ->where('gest_com_entreprise_id', $request->entreprise_id)
                ->get();

            if ($chefs->count() < count($request->chefs)) {
                return response()->json([
                    'error' => 'Tous les chefs doivent être des employés de l\'entreprise'
                ], 400);
            }

            // Si des membres sont envoyés, vérifier qu'ils sont bien des employés de l'entreprise
            if ($request->has('membres')) {
                $membres = GestComUtilisateur::whereIn('id', $request->membres)
                    ->where('gest_com_entreprise_id', $request->entreprise_id)
                    ->get();

                if ($membres->count() < count($request->membres)) {
                    return response()->json([
                        'error' => 'Tous les membres doivent être des employés de l\'entreprise'
                    ], 400);
                }
            }

            // Créer le projet
            $projet = GestProjProjet::create([
                'titre' => $request->titre,
                'date_debut' => $request->date_debut ?? "",
                'date_fin' => $request->date_fin ?? "",
                'description' => $request->description ?? "",
                'gest_com_entreprise_id' => $request->entreprise_id,
            ]);

            // Associer les chefs de projet
            $projet->chefs()->attach($request->chefs);

            // Associer les membres de projet, s'ils existent
            if ($request->has('membres')) {
                $projet->membres()->attach($request->membres);
            }

            // Retourner la réponse de succès
            return response()->json([
                'message' => 'Projet créé avec succès',
                'projet' => $projet,
                'chefs' => $chefs,
                'membres' => $membres?? []
            ], 201);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la création de projets.',
                ['error' => $e->getMessage()]
            );
            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de projets.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ajouterMembres(Request $request, $projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Trouver le projet
            $projet = GestProjProjet::find($projet_id);
            if (!$projet) {
                return response()->json(['error' => 'Projet non trouvé'], 404);
            }

            // Vérifier que l'utilisateur fait partie de l'entreprise du projet
            if ($user->gest_com_entreprise_id != $projet->gest_com_entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce projet'], 403);
            }

            // Vérifier si l'utilisateur est chef de projet
            if (!($projet->chefs()->where('gest_com_utilisateurs.id', $user->id)->exists())) {
                return response()->json(['error' => 'Vous devez être un chef de projet pour retirer un membre'], 403);
            }

            // Valider les nouveaux membres
            $request->validate([
                'membres' => 'required|array',
                'membres.*' => 'exists:gest_com_utilisateurs,id', // Vérifie que chaque membre est un employé existant
            ]);

            // Ajouter les membres au projet sans retirer les existants et sans doublons
            $projet->membres()->syncWithoutDetaching($request->membres);;

            return response()->json([
                'message' => 'Membres ajoutés avec succès au projet',
                'projet' => $projet,
                'membres' => $request->membres
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de l\'ajout des membres.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'ajout des membres.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function retirerMembre(Request $request, $projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Trouver le projet
            $projet = GestProjProjet::find($projet_id);
            if (!$projet) {
                return response()->json(['error' => 'Projet non trouvé'], 404);
            }

            // Vérifier que l'utilisateur fait partie de l'entreprise du projet
            if ($user->gest_com_entreprise_id != $projet->gest_com_entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce projet  '], 403);
            }

            // Vérifier si l'utilisateur est chef de projet
            if (!($projet->chefs()->where('gest_com_utilisateurs.id', $user->id)->exists())) {
                return response()->json(['error' => 'Vous devez être un chef de projet pour retirer un membre'], 403);
            }

            // Valider que le membre à retirer est bien un employé existant
            $request->validate([
                'membre_id' => 'required|exists:gest_com_utilisateurs,id',
            ]);

            // Retirer le membre du projet
            $projet->membres()->detach($request->membre_id);

            return response()->json([
                'message' => 'Membre retiré avec succès du projet',
                'projet' => $projet,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors du retrait du membre.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors du retrait du membre.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ajouterChef(Request $request, $projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Trouver le projet
            $projet = GestProjProjet::find($projet_id);
            if (!$projet) {
                return response()->json(['error' => 'Projet non trouvé'], 404);
            }

            // Vérifier que l'utilisateur fait partie de l'entreprise du projet
            if ($user->gest_com_entreprise_id != $projet->gest_com_entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce projet  '], 403);
            }

            // Vérifier si l'utilisateur est chef de projet
            if (!($projet->chefs()->where('gest_com_utilisateurs.id', $user->id)->exists())) {
                return response()->json(['error' => 'Vous devez être un chef de projet pour ajouter un chef'], 403);
            }

            // Valider que le chef à ajouter est bien un employé existant
            $request->validate([
                'chef_id' => 'required|exists:gest_com_utilisateurs,id',
            ]);

            // Vérifier le nombre actuel de chefs dans le projet
            if ($projet->chefs()->count() >= 3) {
                return response()->json(['error' => 'Le nombre maximum de chefs de projet est atteint (3)'], 400);
            }

            // Ajouter le chef au projet sans retirer les existants et sans doublons
            $projet->chefs()->syncWithoutDetaching($request->chef_id);

            return response()->json([
                'message' => 'Chef ajouté avec succès au projet',
                'projet' => $projet,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de l\'ajout du chef.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'ajout du chef.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function retirerChef(Request $request, $projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Trouver le projet
            $projet = GestProjProjet::find($projet_id);
            if (!$projet) {
                return response()->json(['error' => 'Projet non trouvé'], 404);
            }

            // Vérifier que l'utilisateur fait partie de l'entreprise du projet
            if ($user->gest_com_entreprise_id != $projet->gest_com_entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce projet  '], 403);
            }

            // Vérifier si l'utilisateur est chef de projet
            if (!($projet->chefs()->where('gest_com_utilisateurs.id', $user->id)->exists())) {
                return response()->json(['error' => 'Vous devez être un chef de projet pour ajouter un chef'], 403);
            }

            // Valider que le chef à retirer est bien un employé existant
            $request->validate([
                'chef_id' => 'required|exists:gest_com_utilisateurs,id',
            ]);

            // Vérifier le nombre de chefs restants après la suppression
            $chefsRestants = $projet->chefs()->count();
            if ($chefsRestants <= 1) {
                return response()->json(['error' => 'Il doit rester au moins un chef dans le projet'], 400);
            }

            // Retirer le chef du projet
            $projet->chefs()->detach($request->chef_id);

            return response()->json([
                'message' => 'Chef retiré avec succès du projet',
                'projet' => $projet,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors du retrait du chef.',
                $e->getMessage()
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors du retrait du chef.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAll($entreprise_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que l'administrateur a accès à l'entreprise demandée
            if ($user->gest_com_entreprise_id != $entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à accéder aux projets de cette entreprise'], 403);
            }

            // Récupérer tous les projets associés à l'entreprise
            $projets = GestProjProjet::where('gest_com_entreprise_id', $entreprise_id)
                ->with(['chefs', 'membres']) // Charger les chefs et membres du projet
                ->get();

            // Vérifier si des projets existent
            if ($projets->isEmpty()) {
                return response()->json(['message' => 'Aucun projet trouvé pour cette entreprise.'], 404);
            }

            // Retourner la liste des projets avec leurs détails
            return response()->json([
                'message' => 'Liste des projets récupérée avec succès.',
                'data' => $projets,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la récupération des projets.',
                ['error' => $e->getMessage()]
            );

            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des projets.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProjetsMembre($id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Récupérer tous les projets auxquels l'utilisateur est membre
            $projets = GestProjProjet::with(['chefs', 'membres'])
                ->whereHas('membres', function ($query) use ($id) {
                    $query->where('membre_projet.gest_com_utilisateur_id', $id); // Utilisez le nom de la table ici
                })
                ->where('gest_proj_projets.gest_com_entreprise_id', $user->gest_com_entreprise_id)
                ->get();

            // Vérifier si des projets ont été trouvés
            if ($projets->isEmpty()) {
                return response()->json(['message' => 'Aucun projet trouvé pour cet utilisateur.'], 404);
            }

            return response()->json([
                'message' => 'Liste des projets récupérée avec succès.',
                'data' => $projets,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la récupération des projets.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des projets.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProjetsChefs($id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Récupérer tous les projets auxquels l'utilisateur est chef
            $projets = GestProjProjet::with(['chefs', 'membres'])
                ->whereHas('chefs', function ($query) use ($id) {
                    $query->where('chef_projet.gest_com_utilisateur_id', $id); // Utilisez le nom de la table ici
                })
                ->where('gest_proj_projets.gest_com_entreprise_id', $user->gest_com_entreprise_id)
                ->get();

            // Vérifier si des projets ont été trouvés
            if ($projets->isEmpty()) {
                return response()->json(['message' => 'Aucun projet trouvé pour cet utilisateur.'], 404);
            }

            return response()->json([
                'message' => 'Liste des projets récupérée avec succès.',
                'data' => $projets,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la récupération des projets.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des projets.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function getProjetChef($id, $id_projet)
    // {
    //     try {
    //         // Récupérer l'utilisateur authentifié
    //         $user = Auth::user();

    //         // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
    //         if (!$user || !($user instanceof GestComUtilisateur)) {
    //             return response()->json(['error' => 'Utilisateur non autorisé'], 403);
    //         }

    //         // Récupérer le projet où l'utilisateur est chef et qui correspond à l'id_projet
    //         $projet = GestProjProjet::with(['chefs', 'membres'])
    //             ->whereHas('chefs', function ($query) use ($id) {
    //                 $query->where('chef_projet.gest_com_utilisateur_id', $id); // Filtrer sur l'employé
    //             })
    //             ->where('gest_proj_projets.id', $id_projet)  // Filtrer sur le projet spécifique
    //             ->where('gest_proj_projets.gest_com_entreprise_id', $user->gest_com_entreprise_id)  // S'assurer que le projet appartient à l'entreprise de l'utilisateur
    //             ->first(); // Récupérer un seul projet

    //         // Vérifier si le projet a été trouvé
    //         if (!$projet) {
    //             return response()->json(['message' => 'Projet non trouvé pour cet utilisateur.'], 404);
    //         }

    //         return response()->json([
    //             'message' => 'Projet récupéré avec succès.',
    //             'data' => $projet,
    //         ], 200);
    //     } catch (\Exception $e) {

    //         Log::error(
    //             'Une erreur est survenue lors de la récupération du projet.',
    //             ['error' => $e->getMessage()]
    //         );

    //         return response()->json([
    //             'message' => 'Une erreur est survenue lors de la récupération du projet.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function getProjet( $id_projet)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Récupérer le projet où l'utilisateur est chef et qui correspond à l'id_projet
            $projet = GestProjProjet::with(['chefs', 'membres'])
                        ->where('gest_proj_projets.id', $id_projet)  // Filtrer sur le projet spécifique
                        ->where('gest_proj_projets.gest_com_entreprise_id', $user->gest_com_entreprise_id)  // S'assurer que le projet appartient à l'entreprise de l'utilisateur
                        ->first(); // Récupérer un seul projet


            // Vérifier si le projet a été trouvé
            if (!$projet) {
                return response()->json(['message' => 'Projet non trouvé pour cet utilisateur.'], 404);
            }

            return response()->json([
                'message' => 'Projet récupéré avec succès.',
                'data' => $projet,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la récupération du projet.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function modifierProjet(Request $request, $id_employe, $id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier que l'utilisateur est un chef de projet
            $isChef = GestProjProjet::where('id', $id)
                ->whereHas('chefs', function ($query) use ($id_employe) {
                    $query->where('chef_projet.gest_com_utilisateur_id', $id_employe);
                })
                ->exists();

            if (!$isChef) {
                return response()->json(['error' => 'Utilisateur non autorisé à modifier ce projet'], 403);
            }

            // Validation des données envoyées
            $request->validate([
                'titre' => 'nullable|string',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date',
                'description' => 'nullable|string',
            ]);

            // Récupérer le projet à modifier
            $projet = GestProjProjet::findOrFail($id);

            // Mettre à jour les informations du projet
            $projet->update([
                'titre' => $request->titre ?? $projet->titre,
                'date_debut' => $request->date_debut ?? $projet->date_debut,
                'date_fin' => $request->date_fin ?? $projet->date_fin,
                'description' => $request->description ?? $projet->description,
            ]);

            // Retourner la réponse de succès
            return response()->json([
                'message' => 'Projet modifié avec succès',
                'projet' => $projet,
            ], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la modification du projet.',
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Une erreur est survenue lors de la modification du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function supprimerProjet($projet_id)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Récupérer le projet à supprimer
            $projet = GestProjProjet::find($projet_id);

            // Vérifier si l'utilisateur est chef de projet
            if (!($projet->chefs()->where('gest_com_utilisateurs.id', $user->id)->exists())) {
                return response()->json(['error' => 'Vous devez être un chef de projet pour supprimé un projet'], 403);
            }

            // Vérifier si le projet existe
            if (!$projet) {
                return response()->json(['error' => 'Projet non trouvé'], 404);
            }

            // Supprimer le projet
            $projet->delete();

            // Retourner la réponse de succès
            return response()->json(['message' => 'Projet supprimé avec succès'], 200);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la suppression du projet.',
                ['error' => $e->getMessage()]
            );

            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
