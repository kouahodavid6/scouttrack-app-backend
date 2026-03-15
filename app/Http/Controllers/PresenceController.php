<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresenceController extends Controller
{
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

        // Vérifier la réunion
        $reunion = Reunion::find($reunion_id);

        if (!$reunion) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réunion n’existe pas'
            ], 404);
        }

        try {

            $presences = [];

            foreach ($request->jeune_ids as $jeune_id) {

                $presence = Presence::firstOrCreate([
                    'jeune_id' => $jeune_id,
                    'reunion_id' => $reunion_id
                ]);

                $presences[] = $presence;
            }

            $reunion->is_presented = true;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data' => $presences,
                'message' => 'Présences enregistrées avec succès'
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la présence',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}