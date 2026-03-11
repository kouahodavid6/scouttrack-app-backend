<?php

namespace App\Http\Controllers;

use App\Models\CU;
use App\Models\Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReunionController extends Controller
{
    // Ajouter une réunion
    public function createReunion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_reunion' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut'
        ]);

        // Vérifier que la date est samedi ou dimanche
        $validator->after(function ($validator) use ($request) {

            $date = Carbon::parse($request->date_reunion);

            if (!in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $validator->errors()->add(
                    'date_reunion',
                    'La réunion doit être programmée un samedi ou un dimanche'
                );
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        $user = $request->user();

        $cu = CU::find($user->id);

        if (!$cu) {
            return response()->json([
                'success' => false,
                'message' => 'Chef d’unité introuvable'
            ], 404);
        }

        try {

            $reunion = new Reunion();
            $reunion->date_reunion = $request->date_reunion;
            $reunion->heure_debut = $request->heure_debut;
            $reunion->heure_fin = $request->heure_fin;
            $reunion->cu_id = $cu->id;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data' => $reunion,
                'message' => 'Réunion créée avec succès'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion',
                'erreur' => $e->getMessage()
            ], 500);

        }
    }

    // Lister toutes les réunions
    public function readReunions(Request $request)
    {
        try {
            $user = $request->user();

            $cu = CU::find($user->id);

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d’unité introuvable'
                ], 404);
            }
            $reunions = Reunion::where('cu_id', $cu->id)->get();

            return response()->json([
                'success' => true,
                'data' => $reunions,
                'message' => 'Liste des réunions affichée avec succès',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des réunions',
                'erreur' => $e->getMessage()
            ], 500);

        }
    }

    // Modifier une réunion
    public function updateReunion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date_reunion' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut'
        ]);

        // Vérifier samedi ou dimanche
        $validator->after(function ($validator) use ($request) {

            $date = Carbon::parse($request->date_reunion);

            if (!in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $validator->errors()->add(
                    'date_reunion',
                    'La réunion doit être programmée un samedi ou un dimanche'
                );
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {

            $reunion = Reunion::find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion n’existe pas'
                ], 404);
            }

            $reunion->date_reunion = $request->date_reunion;
            $reunion->heure_debut = $request->heure_debut;
            $reunion->heure_fin = $request->heure_fin;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data' => $reunion,
                'message' => 'Réunion modifiée avec succès'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la réunion',
                'erreur' => $e->getMessage()
            ], 500);

        }
    }

    // Supprimer une réunion
    public function deleteReunion($id)
    {
        try {

            $reunion = Reunion::find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion n’existe pas'
                ], 404);
            }

            $reunion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée avec succès'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réunion',
                'erreur' => $e->getMessage()
            ], 500);

        }
    }
}