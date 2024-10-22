<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\GestComController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {  
    return $request->user();
})->middleware('auth:sanctum');

// Route::post('register/adminsuper', [AuthController::class, 'createAdminSuper']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('logout', [AuthController::class, 'logout']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('register', [GestComController::class, 'store']);
    Route::post('invitation', [GestComController::class, 'storeEmployee']);
    Route::get('entreprises', [EntrepriseController::class, 'getAllEntreprises']);
});

Route::middleware('auth:sanctum')->get('entreprises', [EntrepriseController::class, 'getAllEntreprises']);
Route::middleware('auth:sanctum')->delete('entreprises/{id}', [EntrepriseController::class, 'deleteEntrepriseById']);
Route::middleware('auth:sanctum')->put('entreprises/{id}', [EntrepriseController::class, 'updateEntreprise']);

Route::middleware('auth:sanctum')->put('administrateurs/profile', [UtilisateurController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->get('administrateurs/entreprise', [UtilisateurController::class, 'getEntreprise']);
Route::middleware('auth:sanctum')->get('administrateurs/profile', [UtilisateurController::class, 'getProfile']);
Route::middleware('auth:sanctum')->post('administrateurs/change-password', [UtilisateurController::class, 'changePassword']);

Route::middleware('auth:sanctum')->get('/entreprises/{id_entreprise}/employes', [UtilisateurController::class, 'getAll']);
Route::middleware('auth:sanctum')->delete('employes/{id}', [UtilisateurController::class, 'deleteEmployeById']);
Route::middleware('auth:sanctum')->put('employes/profile', [UtilisateurController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->post('employes/change-password', [UtilisateurController::class, 'changePassword']);

Route::middleware('auth:sanctum')->post('projets',[ProjetController::class, 'creerProjet']);
Route::middleware('auth:sanctum')->get('entreprises/{id_entreprise}/projets', [ProjetController::class, 'getAll']);
Route::middleware('auth:sanctum')->get('entreprises/projets/{id}/projets-membre', [ProjetController::class, 'getProjetsMembre']);
Route::middleware('auth:sanctum')->get('entreprises/projets/{id}/projets-chefs', [ProjetController::class, 'getProjetsChefs']);
Route::middleware('auth:sanctum')->delete('entreprises/projets/{projet_id}', [ProjetController::class, 'supprimerProjet']);
Route::middleware('auth:sanctum')->put('entreprises/projets/{projet_id}', [ProjetController::class, 'ajouterMembres']);
Route::middleware('auth:sanctum')->put('entreprises/projets/chefs/{projet_id}', [ProjetController::class, 'ajouterChef']);

Route::middleware('auth:sanctum')->get('entreprises/projets/{id}/projets-chefs/{id_projet}', [ProjetController::class, 'getProjetChef']);
Route::middleware('auth:sanctum')->put('entreprises/projets/{id_employe}/{id}', [ProjetController::class, 'modifierProjet']);
Route::middleware('auth:sanctum')->put('entreprises/projet/{projet_id}/membre-retire', [ProjetController::class, 'retirerMembre']);
Route::middleware('auth:sanctum')->put('entreprises/projet/{projet_id}/chef-retire', [ProjetController::class, 'retirerChef']);


Route::middleware('auth:sanctum')->prefix('projets/{projet_id}')->group(function () {
    Route::get('taches', [TacheController::class, 'index']);
    Route::post('taches', [TacheController::class, 'store']);
    Route::get('taches/employe', [TacheController::class, 'getTachesParProjetEtAssignable']);
    Route::get('taches/{id}', [TacheController::class, 'show']);
    Route::put('taches/{id}', [TacheController::class, 'update']);
    Route::delete('taches/{id}', [TacheController::class, 'destroy']);
});