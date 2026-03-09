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
use App\Http\Controllers\SuiviJeuneController; // ← AJOUTER CETTE LIGNE
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// -------------------------Toutes les action pour nation-------------------------
Route::middleware('auth:nation')->group(function() {
    Route::post('/create/region', [RegionController::class, 'createRegion']);
    Route::get('/read/regions', [RegionController::class, 'readRegions']);
    Route::delete('/delete/region/{id}', [RegionController::class, 'deleteRegion']);
});

// -------------------------Toutes les action pour région-------------------------
Route::middleware('auth:region')->group(function() {
    Route::post('/create/district', [DistrictController::class, 'createDistrict']);
    Route::get('/read/districts', [DistrictController::class, 'readDistricts']);
    Route::delete('/delete/district/{id}', [DistrictController::class, 'deleteDistrict']);
});

// -------------------------Toutes les action pour district-------------------------
Route::middleware('auth:district')->group(function() {
    Route::post('/create/groupe', [GroupeController::class, 'createGroupe']);
    Route::get('/read/groupes', [GroupeController::class, 'readGroupes']);
    Route::delete('/delete/groupe/{id}', [GroupeController::class, 'deleteGroupe']);
});

// -------------------------Toutes les action pour groupe-------------------------
Route::middleware('auth:groupe')->group(function() {
    // Opérations CRUD pour chef d'unités
    Route::post('/create/cu', [CUController::class, 'createCU']);
    Route::get('/read/cus', [CUController::class, 'readCUs']);
    Route::delete('/delete/cu/{id}', [CUController::class, 'deleteCU']);

    // Opérations CRUD pour branches
    Route::post('/create/branche', [BrancheController::class, 'createBranche']);
    Route::get('/read/branches', [BrancheController::class, 'readBranches']);
    Route::put('/update/branche/{id}', [BrancheController::class, 'updateBranche']);
    Route::delete('/delete/branche/{id}', [BrancheController::class, 'deleteBranche']);
});

// -------------------------Toutes les action pour chef d'unité-------------------------
Route::middleware('auth:cu')->group(function() {
    // Opérations CRUD pour jeunes
    Route::post('/create/jeune', [JeuneController::class, 'createJeune']);
    Route::get('/read/jeunes', [JeuneController::class, 'readJeunes']);
    Route::delete('/delete/jeune/{id}', [JeuneController::class, 'deleteJeune']);

    // Opérations CRUD pour étapes
    Route::post('/create/etape', [EtapeController::class, 'createEtape']);
    Route::get('/read/etapes', [EtapeController::class, 'readEtapes']);
    Route::put('/update/etape/{id}', [EtapeController::class, 'updateEtape']);
    Route::delete('/delete/etape/{id}', [EtapeController::class, 'deleteEtape']);

    // Opérations CRUD pour les activités
    Route::post('/create/activite', [ActiviteController::class, 'createActivite']);
    Route::get('/read/activites', [ActiviteController::class, 'readActivites']);
    Route::post('/update/activite/{id}', [ActiviteController::class, 'updateActivite']);
    Route::delete('/delete/activite/{id}', [ActiviteController::class, 'deleteActivite']);

    // ============ NOUVELLES ROUTES POUR LE SUIVI DES JEUNES ============
    
    // Récupérer les jeunes du chef connecté
    Route::get('/chef/mes-jeunes', [SuiviJeuneController::class, 'getMesJeunes']);
    
    // Suivi complet : activités par étape avec statut de participation
    Route::get('/suivi/jeunes', [SuiviJeuneController::class, 'getSuiviComplet']);
    
    // Valider la participation d'un jeune à une activité
    Route::post('/suivi/valider', [SuiviJeuneController::class, 'validerParticipation']);
    
    // Vérifier si une étape est complète pour un jeune
    Route::get('/suivi/etape-complete', [SuiviJeuneController::class, 'checkEtapeComplete']);
    
    // Statistiques d'un jeune
    Route::get('/suivi/jeune/{id}/statistiques', [SuiviJeuneController::class, 'getStatistiquesJeune']);
});

// -------------------------Toutes les action pour jeune-------------------------
Route::middleware('auth:jeune')->group(function() {
    // Consultation de la progression (lecture seule)
    Route::get('/mon-suivi', [JeuneProgressionController::class, 'getMaProgression']);
    Route::get('/mes-statistiques', [JeuneProgressionController::class, 'getMesStatistiques']);
});