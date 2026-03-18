<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    /**
     * Enregistrer les présences pour une réunion
     */
    public function presence(Request $request, $reunion_id)
    {
        $validator = Validator::make($request->all(), [
            'jeune_ids' => 'required|array',
            'jeune_ids.*' => 'exists:jeunes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reunion = Reunion::find($reunion_id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réunion n’existe pas'
                ], 404);
            }

            // Vérifier que la réunion n'a pas déjà été présentée
            if ($reunion->is_presented) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les présences ont déjà été enregistrées pour cette réunion'
                ], 422);
            }

            // Supprimer les anciennes présences
            Presence::where('reunion_id', $reunion_id)->delete();

            // Créer les nouvelles présences
            $presences = [];
            foreach ($request->jeune_ids as $jeune_id) {
                $presence = Presence::create([
                    'jeune_id' => $jeune_id,
                    'reunion_id' => $reunion_id
                ]);
                $presences[] = $presence;
            }

            // Marquer la réunion comme présentée
            $reunion->is_presented = true;
            $reunion->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'reunion' => $reunion,
                    'presences' => $presences
                ],
                'message' => 'Présences enregistrées avec succès'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}