<?php

namespace App\Http\Controllers;

use App\Models\Branche;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BrancheController extends Controller
{
    // Ajouter une branche
    public function createBranche (Request $request) {
        $validator = Validator::make($request->all(), [
            'nomBranche' => 'required|string|max:255',
            'ordreBranche' => 'nullable|integer|min:0' // ← AJOUT ICI
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $branche = new Branche();
            $branche->nomBranche = $request->nomBranche;
            $branche->ordreBranche = $request->ordreBranche ?? 0; // ← AJOUT ICI
            $branche->save();

            return response()->json([
                'success' => true,
                'data' => $branche,
                'message' => 'Branche créée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la branche',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Lister toutes les branches
    public function readBranches () {
        try {
            // Trier par ordreBranche puis par nom (optionnel)
            $branches = Branche::orderBy('ordreBranche')->orderBy('nomBranche')->get();

            return response()->json([
                'success' => true,
                'data' => $branches,
                'message' => 'Liste des branches affichée avec succès'
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des branches',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Modifier une branche
    public function updateBranche (Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'nomBranche' => 'nullable|string|max:255',
            'ordreBranche' => 'nullable|integer|min:0' // ← AJOUT ICI
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $branche = Branche::find($id);

            if (!$branche) {
                return response()->json([
                    'success' => false,
                    'message' =>'Cette branche n’existe pas'
                ], 404);
            }
            
            $branche->nomBranche = $request->nomBranche ?? $branche->nomBranche;
            $branche->ordreBranche = $request->ordreBranche ?? $branche->ordreBranche; // ← AJOUT ICI
            $branche->save();

            return response()->json([
                'success' => true,
                'data' => $branche,
                'message' => 'Branche modifiée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la branche',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une branche
    public function deleteBranche ($id) {
        try {
            $branche = Branche::find($id);

            if (!$branche) {
                return response()->json([
                    'success' => false,
                    'message' =>'Cette branche n’existe pas'
                ], 404);
            }
            $branche->delete();

            return response()->json([
                'success' => true,
                'message' => 'Branche supprimée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la branche',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}