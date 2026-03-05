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
            'nom' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $etape = new Etape();
            $etape->nom = $request->nom;
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
    public function readEtapes () {
        try {
            $etapes = Etape::all();

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
            'nom' => 'required|string|max:255'
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
                    'message' => 'cette étape n’existe pas'
                ], 404);
            }
            $etape->nom = $request->nom;
            $etape->save();

            return response()->json([
                'success' => true,
                'data' => $etape,
                'message' => 'Étape modifiée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’étape',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une étape
    public function deleteEtape ($id) {
        try {
            $etape = Etape::find($id);

            if (!$etape) {
                return response()->json([
                    'success' => false,
                    'message' => 'cette étape n’existe pas'
                ], 404);
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
