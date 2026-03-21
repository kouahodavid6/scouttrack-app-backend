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
            // L'utilisateur authentifié EST DÉJÀ un CU
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité non authentifié"
                ], 401);
            }

            // Vérifier que c'est bien un CU (role = 1)
            if ($cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Utilisateur non autorisé"
                ], 403);
            }

            $reunion = new Reunion();
            $reunion->date_reunion = $request->date_reunion;
            $reunion->heure_debut = $request->heure_debut;
            $reunion->heure_fin = $request->heure_fin;
            $reunion->cu_id = $cu->id; // Utilise directement l'ID du CU connecté
            $reunion->is_presented = false;
            $reunion->save();

            return response()->json([
                'success' => true,
                'data' => $reunion,
                'message' => 'Réunion créée avec succès'
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réunion'
            ], 500);
        }
    }

    /**
     * Lister toutes les réunions du CU connecté
     */
    public function readReunions(Request $request)
    {
        try {
            // L'utilisateur authentifié EST DÉJÀ un CU
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité non authentifié"
                ], 401);
            }

            // Vérifier que c'est bien un CU (role = 1)
            if ($cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Utilisateur non autorisé"
                ], 403);
            }

            $reunions = Reunion::with('presences.jeune')
                ->where('cu_id', $cu->id)
                ->orderBy('date_reunion', 'desc')
                ->get();

            $formattedReunions = [];

            foreach ($reunions as $reunion) {
                $presences = [];
                
                if ($reunion->presences && $reunion->presences->count() > 0) {
                    $presences = $reunion->presences->map(function ($presence) {
                        if ($presence->jeune) {
                            return [
                                'id' => $presence->jeune->id,
                                'nom' => $presence->jeune->nom,
                                'age' => $presence->jeune->age,
                                'photo' => $presence->jeune->photo,
                                'branche' => $presence->jeune->branche
                            ];
                        }
                        return null;
                    })->filter()->values()->toArray();
                }

                $formattedReunions[] = [
                    'id' => $reunion->id,
                    'date_reunion' => $reunion->date_reunion,
                    'heure_debut' => $reunion->heure_debut,
                    'heure_fin' => $reunion->heure_fin,
                    'is_presented' => $reunion->is_presented,
                    'cu_id' => $reunion->cu_id,
                    'created_at' => $reunion->created_at,
                    'updated_at' => $reunion->updated_at,
                    'presences' => $presences
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedReunions,
                'message' => 'Liste des réunions récupérée avec succès',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des réunions'
            ], 500);
        }
    }

    /**
     * Récupérer une réunion avec ses présences
     */
    public function getReunionById(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité non authentifié"
                ], 401);
            }

            $reunion = Reunion::with('presences.jeune')
                ->where('cu_id', $cu->id)
                ->find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette réunion n'existe pas",
                ], 404);
            }

            $presences = [];
            if ($reunion->presences && $reunion->presences->count() > 0) {
                $presences = $reunion->presences->map(function ($presence) {
                    if ($presence->jeune) {
                        return [
                            'id' => $presence->jeune->id,
                            'nom' => $presence->jeune->nom,
                            'age' => $presence->jeune->age,
                            'photo' => $presence->jeune->photo,
                            'branche' => $presence->jeune->branche
                        ];
                    }
                    return null;
                })->filter()->values();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $reunion->id,
                    'date_reunion' => $reunion->date_reunion,
                    'heure_debut' => $reunion->heure_debut,
                    'heure_fin' => $reunion->heure_fin,
                    'is_presented' => $reunion->is_presented,
                    'cu_id' => $reunion->cu_id,
                    'created_at' => $reunion->created_at,
                    'updated_at' => $reunion->updated_at,
                    'presences' => $presences
                ],
                'message' => 'Réunion récupérée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la réunion'
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
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut'
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
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité non authentifié"
                ], 401);
            }

            $reunion = Reunion::where('cu_id', $cu->id)->find($id);

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
                'message' => 'Erreur lors de la modification de la réunion'
            ], 500);
        }
    }

    /**
     * Supprimer une réunion
     */
    public function deleteReunion(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => "Chef d'unité non authentifié"
                ], 401);
            }

            $reunion = Reunion::where('cu_id', $cu->id)->find($id);

            if (!$reunion) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette réunion n'existe pas"
                ], 404);
            }

            // Supprimer d'abord les présences associées
            Presence::where('reunion_id', $id)->delete();
            
            // Puis supprimer la réunion
            $reunion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réunion supprimée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réunion'
            ], 500);
        }
    }

    /**
 * Récupérer les réunions pour un jeune (lecture seule)
 * Endpoint: GET /api/jeune/reunions
 */
public function getReunionsForJeune(Request $request)
{
    try {
        $jeune = $request->user();
        
        if (!$jeune) {
            return response()->json([
                'success' => false,
                'message' => 'Jeune non authentifié'
            ], 401);
        }

        // Vérifier que le jeune a un CU
        if (!$jeune->cu_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Vous n\'êtes pas assigné à un chef d\'unité'
            ], 200);
        }

        // Récupérer les réunions du CU du jeune
        $reunions = Reunion::with('presences')
            ->where('cu_id', $jeune->cu_id)
            ->orderBy('date_reunion', 'desc')
            ->get();

        $formattedReunions = [];

        foreach ($reunions as $reunion) {
            // Vérifier si le jeune est présent
            $presence = $reunion->presences->where('jeune_id', $jeune->id)->first();
            
            $formattedReunions[] = [
                'id' => $reunion->id,
                'date_reunion' => $reunion->date_reunion,
                'heure_debut' => $reunion->heure_debut,
                'heure_fin' => $reunion->heure_fin,
                'is_presented' => $reunion->is_presented,
                'is_present' => $presence ? true : false,
                'presence_id' => $presence ? $presence->id : null,
                'presence_date' => $presence ? $presence->created_at : null,
                'created_at' => $reunion->created_at,
                'updated_at' => $reunion->updated_at,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedReunions,
            'message' => 'Réunions récupérées avec succès'
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des réunions',
            'error' => $e->getMessage()
        ], 500);
    }
}
}