<?php

namespace App\Http\Controllers;

use App\Models\GestComEntreprise;
use App\Models\GestComUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function createAdminSuper(Request $request)
    {
        try {
            // Validation des données
            $request->validate([
                'nom' => 'required|string|max:255',
                'email' => 'required|email|unique:gest_com_utilisateurs,email',
            ]);

            // Générer un mot de passe aléatoire
            $mot_de_passe = Str::random(8);

            // Création de l'utilisateur avec le rôle d'administrateur supérieur
            $utilisateur = GestComUtilisateur::create([
                'nom' => $request->nom_utilisateur,
                'email' => $request->email,
                'telephone' => $request->telephone ?? "",
                'poste' => $request->poste ?? "",
                'role' => 'adminSuper', // Attribuer le rôle d'administrateur supérieur
                'mot_de_passe' => $mot_de_passe, // Utiliser le mot de passe généré
            ]);

            // Envoyer le mot de passe par email à l'administrateur
            Mail::raw(
                "Bonjour {$request->nom_utilisateur},\n\nVotre compte administrateur supérieur a été créé avec succès.\n\nVoici vos informations de connexion :\nEmail : {$request->email}\nMot de passe : {$mot_de_passe}\n\nNous vous recommandons de changer votre mot de passe dès votre première connexion.\n\nMerci de faire partie de notre équipe !\n\nCordialement,\nL'équipe de gestion",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Création de votre compte administrateur supérieur');
                }
            );

            // Réponse en cas de succès
            return response()->json([
                'message' => 'Le compte administrateur supérieur a été créé avec succès. Un email contenant les informations de connexion a été envoyé à l\'adresse.'
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
                'message' => 'Une erreur est survenue lors de la création du compte. Veuillez réessayer plus tard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Méthode de Connexion
    public function login(Request $request)
    {
        try {
            // Valider l'email et le mot de passe
            $request->validate([
                'email' => 'required|email',
                'mot_de_passe' => 'required',
            ]);

            $user = GestComUtilisateur::where('email', $request->email)->first();

        if ($user && Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
            // Si tout est correct, générer un token
            $entreprise = GestComEntreprise::find($user->gest_com_entreprise_id);
            $nom_entreprise = $entreprise ? $entreprise->nom : "";

            $token = $user->createToken('auth_token', ['role' => $user->role])->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'token' => $token,
                'rôle' => $user->role,
                'utilisateur' => $user,
                'nom_entreprise' => $nom_entreprise
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la connection',
                ['error' => $e->getMessage()]
            );

            // Gérer les erreurs
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Méthode de déconnexion
    public function logout(Request $request)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            if ($user) {
                // Révoquer le token actuel utilisé par l'utilisateur
                $request->user()->currentAccessToken()->delete();

                return response()->json([
                    'message' => 'Déconnexion réussie',
                ]);
            }

            return response()->json(['message' => 'Non authentifié'], 401);
        } catch (\Exception $e) {

            Log::error(
                'Une erreur est survenue lors de la déconnexion.',
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
