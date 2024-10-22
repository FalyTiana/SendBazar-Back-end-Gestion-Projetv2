<?php

namespace App\Http\Controllers;

use App\Models\GestComEntreprise;
use App\Models\GestComUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log; // Assure-toi d'importer Log
use Illuminate\Support\Str;

class GestComController extends Controller
{
    /**
     * Enregistre une nouvelle entreprise et un utilisateur avec le rôle d'administrateur.
     */
    public function store(Request $request)
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

            // Validation des données
            $request->validate([
                'nom_entreprise' => 'required|string|max:255',
                'nom_utilisateur' => 'required|string|max:255',
                'email' => 'required|email|unique:gest_com_utilisateurs,email',
            ]);

            // Création de l'entreprise
            $entreprise = GestComEntreprise::create([
                'nom' => $request->nom_entreprise,
            ]);

            // Générer un mot de passe aléatoire
            $mot_de_passe = Str::random(8);

            // Création de l'utilisateur avec le rôle d'administrateur
            $utilisateur = GestComUtilisateur::create([
                'nom' => $request->nom_utilisateur,
                'email' => $request->email,
                'telephone' => $request->telephone ?? "",
                'poste' => $request->poste ?? "",
                'role' => 'admin', // Attribuer le rôle d'administrateur
                'mot_de_passe' => $mot_de_passe, // Utiliser le mot de passe généré
                'gest_com_entreprise_id' => $entreprise->id, // ID de l'entreprise
            ]);

            // Envoyer le mot de passe par email à l'administrateur
            Mail::raw(
                "Bonjour {$request->nom_utilisateur},\n\nVotre compte administrateur a été créé avec succès.\n\nVoici vos informations de connexion :\nEmail : {$request->email}\nMot de passe : {$mot_de_passe}\n\nNous vous recommandons de changer votre mot de passe dès votre première connexion.\n\nMerci de faire partie de notre équipe !\n\nCordialement,\nL'équipe de gestion",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Création de votre compte administrateur');
                }
            );

            // Réponse en cas de succès
            return response()->json([
                'message' => 'Le compte a été créé avec succès, un email a été envoyé.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Gérer les erreurs de validation
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la création du compte.',
                ['error' => $e->getMessage()]
            );

            // Gérer les autres erreurs, comme l'email déjà utilisé
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du compte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Enregistre une nouvelle entreprise et un utilisateur avec le rôle d'administrateur.
     */
    public function storeEmployee(Request $request)
    {
        try {

            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est authentifié et est une instance de GestComUtilisateur
            if (!$user || !($user instanceof GestComUtilisateur)) {
                return response()->json(['error' => 'Utilisateur non autorisé'], 403);
            }

            // Vérifier si l'utilisateur a le rôle adminSuper
            if ($user->role != "admin") {
                return response()->json(['error' => 'Vous n\'êtes pas un admin'], 403);
            }

            // Validation des données
            $request->validate([
                'nom' => 'required|string|max:255',
                'email' => 'required|email|unique:gest_com_utilisateurs,email',
                'entreprise_id' => 'required',
            ]);

             // Vérification que l'administrateur est bien l'administrateur de l'entreprise
             if ($user->gest_com_entreprise_id != $request->entreprise_id) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à inviter des employés pour cette entreprise'], 403);
            }

            // Générer un mot de passe aléatoire
            $mot_de_passe = Str::random(8);

            // Création de l'utilisateur avec le rôle d'administrateur
            $utilisateur = GestComUtilisateur::create([
                'nom' => $request->nom,
                'email' => $request->email,
                'telephone' => $request->telephone ?? "",
                'poste' => $request->poste ?? "",
                'role' => 'employe', // Attribuer le rôle d'administrateur
                'mot_de_passe' => $mot_de_passe, // Utiliser le mot de passe généré
                'gest_com_entreprise_id' => $request->entreprise_id, // ID de l'entreprise
            ]);

            // Envoyer le mot de passe par email à l'administrateur
            Mail::raw(
                "Bonjour {$request->nom},\n\nVotre compte employé au sein de l'entreprise {$user->entreprise->nom} a été créé avec succès.\n\nVoici vos informations de connexion :\nEmail : {$request->email}\nMot de passe : {$mot_de_passe}\n\nNous vous recommandons de changer votre mot de passe dès votre première connexion.\n\nMerci de faire partie de notre équipe !\n\nCordialement,\nL'équipe de gestion",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Création de votre compte employé');
                }
            );

            // Réponse en cas de succès
            return response()->json([
                'message' => 'Le compte a été créé avec succès, un email a été envoyé.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Gérer les erreurs de validation
            return response()->json([
                'message' => 'Erreur de validation des données.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error(
                'Une erreur est survenue lors de la création du compte employe.',
                ['error' => $e->getMessage()]
            );

            // Gérer les autres erreurs, comme l'email déjà utilisé
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du compte.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
