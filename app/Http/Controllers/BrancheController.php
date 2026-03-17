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
            'nomBranche' => 'required|string|max:255|unique:branches,nomBranche',
            'ordreBranche' => 'required|integer|min:1|unique:branches,ordreBranche',
            'age_min' => 'required|integer|min:4|max:20',
            'age_max' => 'required|integer|min:5|max:21|gt:age_min'
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
            $branche->ordreBranche = $request->ordreBranche ?? 1;
            $branche->age_min = $request->age_min;
            $branche->age_max = $request->age_max;
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
            $branches = Branche::orderBy('ordreBranche')->get();

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
            'nomBranche' => 'required|string|max:255|unique:branches,nomBranche',
            'ordreBranche' => 'required|integer|min:1|unique:branches,ordreBranche',
            'age_min' => 'required|integer|min:4|max:20',
            'age_max' => 'required|integer|min:5|max:21|gt:age_min'
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
            $branche->ordreBranche = $request->ordreBranche ?? $branche->ordreBranche;
            $branche->age_min = $request->age_min ?? $branche->age_min;
            $branche->age_max = $request->age_max ?? $branche->age_max;
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

            // Vérifier s'il y a des jeunes ou des CU liés
            if ($branche->jeunes()->count() > 0 || $branche->cus()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette branche car elle contient des jeunes ou des chefs d\'unité'
                ], 422);
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

    // Récupérer la branche du chef d'unité connecter pour l'espace cu
    public function showBrancheCU($id)
    {
        try {
            $branche = Branche::find($id);
            
            if (!$branche) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branche non trouvée'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $branche,
                'message' => 'Branche récupérée avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}