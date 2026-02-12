<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\CUController;
use App\Http\Controllers\JeuneController;
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
    Route::post('/create/cu', [CUController::class, 'createCU']);
    Route::get('/read/cus', [CUController::class, 'readCUs']);
    Route::delete('/delete/cu/{id}', [CUController::class, 'deleteCU']);
});

// -------------------------Toutes les action pour chef d'unité-------------------------
Route::middleware('auth:cu')->group(function() {
    Route::post('/create/jeune', [JeuneController::class, 'createJeune']);
    Route::get('/read/jeunes', [JeuneController::class, 'readJeunes']);
    Route::delete('/delete/jeune/{id}', [JeuneController::class, 'deleteJeune']);
});

// -------------------------Toutes les action pour jeune-------------------------
Route::middleware('auth:jeune')->group(function() {});