<?php

use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrancheController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\CUController;
use App\Http\Controllers\EtapeController;
use App\Http\Controllers\JeuneController;
use App\Http\Controllers\JeuneProgressionController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\SuiviJeuneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route publique pour la connexion
Route::post('/login', [AuthController::class, 'login']);

// ------------------------- Routes pour Nation -------------------------
Route::middleware('auth:nation')->group(function() {
    Route::post('/create/region', [RegionController::class, 'createRegion']);
    Route::get('/read/regions', [RegionController::class, 'readRegions']);
    Route::delete('/delete/region/{id}', [RegionController::class, 'deleteRegion']);
});

// ------------------------- Routes pour Région -------------------------
Route::middleware('auth:region')->group(function() {
    Route::post('/create/district', [DistrictController::class, 'createDistrict']);
    Route::get('/read/districts', [DistrictController::class, 'readDistricts']);
    Route::delete('/delete/district/{id}', [DistrictController::class, 'deleteDistrict']);
});

// ------------------------- Routes pour District -------------------------
Route::middleware('auth:district')->group(function() {
    Route::post('/create/groupe', [GroupeController::class, 'createGroupe']);
    Route::get('/read/groupes', [GroupeController::class, 'readGroupes']);
    Route::delete('/delete/groupe/{id}', [GroupeController::class, 'deleteGroupe']);
});

// ------------------------- Routes pour Groupe -------------------------
Route::middleware('auth:groupe')->group(function() {
    // Opérations CRUD pour chefs d'unités
    Route::post('/create/cu', [CUController::class, 'createCU']);
    Route::get('/read/cus', [CUController::class, 'readCUs']);
    Route::delete('/delete/cu/{id}', [CUController::class, 'deleteCU']);

    // Opérations CRUD pour branches
    Route::post('/create/branche', [BrancheController::class, 'createBranche']);
    Route::get('/read/branches', [BrancheController::class, 'readBranches']);
    Route::put('/update/branche/{id}', [BrancheController::class, 'updateBranche']);
    Route::delete('/delete/branche/{id}', [BrancheController::class, 'deleteBranche']);
});

// ------------------------- Routes pour Chef d'Unité (CU) -------------------------
Route::middleware('auth:cu')->group(function() {
    // Opérations CRUD pour jeunes
    Route::post('/create/jeune', [JeuneController::class, 'createJeune']);
    Route::get('/read/jeunes', [JeuneController::class, 'readJeunes']);
    Route::delete('/delete/jeune/{id}', [JeuneController::class, 'deleteJeune']);
    // Route pour récupérer une branche spécifique
    Route::get('/branche/{id}', [BrancheController::class, 'showBrancheCU']);

    // Opérations CRUD pour étapes
    Route::post('/create/etape', [EtapeController::class, 'createEtape']);
    Route::get('/read/etapes', [EtapeController::class, 'readEtapes']);
    Route::put('/update/etape/{id}', [EtapeController::class, 'updateEtape']);
    Route::delete('/delete/etape/{id}', [EtapeController::class, 'deleteEtape']);

    // Opérations CRUD pour activités
    Route::post('/create/activite', [ActiviteController::class, 'createActivite']);
    Route::get('/read/activites', [ActiviteController::class, 'readActivites']);
    Route::post('/update/activite/{id}', [ActiviteController::class, 'updateActivite']);
    Route::delete('/delete/activite/{id}', [ActiviteController::class, 'deleteActivite']);

    // Routes pour le suivi des jeunes
    Route::get('/chef/mes-jeunes', [SuiviJeuneController::class, 'getMesJeunes']);
    Route::get('/suivi/jeunes', [SuiviJeuneController::class, 'getSuiviComplet']);
    Route::post('/suivi/valider', [SuiviJeuneController::class, 'validerParticipation']);
    Route::delete('/suivi/invalider/{participation_id}', [SuiviJeuneController::class, 'supprimerParticipation']);
    Route::get('/suivi/etape-complete', [SuiviJeuneController::class, 'checkEtapeComplete']);
    Route::get('/suivi/jeune/{id}/statistiques', [SuiviJeuneController::class, 'getStatistiquesJeune']);

    // Routes pour les réunions et présences
    Route::post('/create/reunion', [ReunionController::class, 'createReunion']);
    Route::get('/read/reunions', [ReunionController::class, 'readReunions']);
    Route::get('/read/reunion/{id}', [ReunionController::class, 'getReunionById']);
    Route::put('/update/reunion/{id}', [ReunionController::class, 'updateReunion']);
    Route::delete('/delete/reunion/{id}', [ReunionController::class, 'deleteReunion']);
    Route::post('/valider/presence/{reunion_id}', [PresenceController::class, 'presence']);
});

// ------------------------- Routes pour Jeune -------------------------
Route::middleware('auth:jeune')->group(function() {
    // Consultation de la progression (lecture seule)
    Route::get('/mon-suivi', [JeuneProgressionController::class, 'getMaProgression']);
    Route::get('/mes-statistiques', [JeuneProgressionController::class, 'getMesStatistiques']);
});