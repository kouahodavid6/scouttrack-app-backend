<?php

namespace App\Http\Controllers;

use App\Models\CU;
use App\Models\Presence;
use App\Models\Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReunionController extends Controller
{
    /**
     * Ajouter une nouvelle réunion
     */
    public function createReunion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_reunion' => 'required|date|after_or_equal:today',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut'
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

        try {
            $user = $request->user();
            $cu   = CU::find($user->id);

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité introuvable"
                ], 404);
            }

            $reunion               = new Reunion();
            $reunion->date_reunion = $request->date_reunion;
            $reunion->heure_debut  = $request->heure_debut;
            $reunion->heure_fin    = $request->heure_fin;
            $reunion->cu_id        = $cu->id;
            $reunion->is_presented = false;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data'    => $reunion,
                'message' => 'Réunion créée avec succès'
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister toutes les réunions du CU connecté
     * Retourne la clé "presences" (pluriel) dans chaque réunion.
     */
    public function readReunions(Request $request)
    {
        try {
            $user = $request->user();
            $cu   = CU::find($user->id);

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité introuvable"
                ], 404);
            }

            $reunions = Reunion::with('presences.jeune')
                ->where('cu_id', $cu->id)
                ->orderBy('date_reunion', 'desc')
                ->get();

            $formattedReunions = [];

            foreach ($reunions as $reunion) {
                $formattedReunion = [
                    'id'           => $reunion->id,
                    'date_reunion' => $reunion->date_reunion,
                    'heure_debut'  => $reunion->heure_debut,
                    'heure_fin'    => $reunion->heure_fin,
                    'is_presented' => $reunion->is_presented,
                    'cu_id'        => $reunion->cu_id,
                    'created_at'   => $reunion->created_at,
                    'updated_at'   => $reunion->updated_at,
                    'presences'    => []   // ← clé "presences" (pluriel)
                ];

                if ($reunion->presences && $reunion->presences->count() > 0) {
                    $formattedReunion['presences'] = $reunion->presences->map(function ($presence) {
                        return [
                            'id'      => $presence->jeune_id,
                            'nom'     => $presence->jeune->nom,
                            'age'     => $presence->jeune->age,
                            'photo'   => $presence->jeune->photo,
                            'branche' => $presence->jeune->branche
                        ];
                    })->toArray();
                }

                $formattedReunions[] = $formattedReunion;
            }

            return response()->json([
                'success' => true,
                'data'    => $formattedReunions,
                'message' => 'Liste des réunions récupérée avec succès',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des réunions',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une réunion avec ses présences
     * ✅ CORRECTION : clé "presences" (pluriel) — alignée avec readReunions
     *    et avec HistoriquePresenceModal qui lit reunion?.presences
     */
    public function getReunionById($id)
    {
        try {
            $reunion = Reunion::with('presences.jeune')->find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette réunion n'existe pas",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => $reunion->id,
                    'date_reunion' => $reunion->date_reunion,
                    'heure_debut'  => $reunion->heure_debut,
                    'heure_fin'    => $reunion->heure_fin,
                    'is_presented' => $reunion->is_presented,
                    'cu_id'        => $reunion->cu_id,
                    'created_at'   => $reunion->created_at,
                    'updated_at'   => $reunion->updated_at,
                    // ✅ "presences" (pluriel) — cohérent avec readReunions et le frontend
                    'presences'    => $reunion->presences->map(function ($presence) {
                        return [
                            'id'      => $presence->jeune_id,
                            'nom'     => $presence->jeune->nom,
                            'age'     => $presence->jeune->age,
                            'photo'   => $presence->jeune->photo,
                            'branche' => $presence->jeune->branche
                        ];
                    })
                ],
                'message' => 'Réunion récupérée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la réunion',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifier une réunion
     */
    public function updateReunion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date_reunion' => 'required|date',
            'heure_debut'  => 'required|date_format:H:i',
            'heure_fin'    => 'required|date_format:H:i|after:heure_debut'
        ]);

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
                    'message' => "Cette réunion n'existe pas"
                ], 404);
            }

            if ($reunion->is_presented) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de modifier une réunion dont l'appel a déjà été fait"
                ], 422);
            }

            $reunion->date_reunion = $request->date_reunion;
            $reunion->heure_debut  = $request->heure_debut;
            $reunion->heure_fin    = $request->heure_fin;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data'    => $reunion,
                'message' => 'Réunion modifiée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la réunion',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une réunion
     */
    public function deleteReunion($id)
    {
        try {
            $reunion = Reunion::find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette réunion n'existe pas"
                ], 404);
            }

            Presence::where('reunion_id', $id)->delete();
            $reunion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réunion',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}