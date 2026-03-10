<?php

namespace App\Http\Controllers;

use App\Models\Etape;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EtapeController extends Controller
{
    //Ajouter une étape
    public function createEtape (Request $request) {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'numEtape' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // ✅ Récupérer le CU connecté
            $cu = $request->user(); // L'utilisateur est un CU (guard 'cu')
            
            if (!$cu || !$cu->branche_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être associé à une branche pour créer une étape'
                ], 403);
            }

            $etape = new Etape();
            $etape->nom = $request->nom;
            $etape->numEtape = $request->numEtape;
            // ✅ Utiliser la branche du CU connecté
            $etape->branche_id = $cu->branche_id;
            $etape->save();

            return response()->json([
                'success' => true,
                'data' => $etape,
                'message' => 'Étape créée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’étape',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Lister les étapes
    public function readEtapes (Request $request) {
        try {
            // ✅ Optionnel : filtrer par la branche du CU connecté
            $cu = $request->user();
            
            $etapes = Etape::with('branche')
                ->where('branche_id', $cu->branche_id) // Seulement les étapes de sa branche
                ->orderBy('numEtape', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $etapes,
                'message' => 'Liste des étapes affichée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des étapes',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Modifier une étape
    public function updateEtape (Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'nom' => 'nullable|string|max:255',
            'numEtape' => 'nullable|integer|min:1'
            // ✅ SUPPRIMER branche_id de la validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $etape = Etape::find($id);

            if (!$etape) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette étape n’existe pas'
                ], 404);
            }

            // ✅ Vérifier que l'étape appartient bien à la branche du CU connecté
            $cu = $request->user();
            if ($etape->branche_id !== $cu->branche_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à modifier cette étape'
                ], 403);
            }

            $etape->nom = $request->nom ?? $etape->nom;
            $etape->numEtape = $request->numEtape ?? $etape->numEtape;
            // ✅ NE PAS MODIFIER la branche_id
            $etape->save();

            return response()->json([
                'success' => true,
                'data' => $etape,
                'message' => 'Étape modifiée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l’étape',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une étape
    public function deleteEtape (Request $request, $id) {
        try {
            $etape = Etape::find($id);

            if (!$etape) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette étape n’existe pas'
                ], 404);
            }

            // ✅ Vérifier que l'étape appartient bien à la branche du CU connecté
            $cu = $request->user();
            if ($etape->branche_id !== $cu->branche_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à supprimer cette étape'
                ], 403);
            }

            $etape->delete();

            return response()->json([
                'success' => true,
                'message' => 'Étape supprimée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l’étape',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}