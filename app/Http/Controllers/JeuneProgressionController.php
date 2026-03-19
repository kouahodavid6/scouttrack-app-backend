<?php

namespace App\Http\Controllers;

use App\Models\Act_Jeune;
use App\Models\Jeune;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JeuneProgressionController extends Controller
{
    /**
     * RÉCUPÉRER LA PROGRESSION DU JEUNE CONNECTÉ
     * Endpoint: GET /api/mon-suivi
     */
    public function getMaProgression(Request $request)
    {
        try {
            // Récupérer le jeune connecté
            $jeune = $request->user(); // L'utilisateur est un jeune (guard 'jeune')
            
            // Charger ses relations avec tri de la branche par ordreBranche
            $jeune->load(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }, 'branche.etapes' => function($query) {
                $query->orderBy('numEtape'); // Trier les étapes par numéro d'étape
            }, 'branche.etapes.activites']);
            
            // Récupérer ses participations
            $participations = Act_Jeune::where('jeune_id', $jeune->id)->get();
            
            // Construire le tableau de participation
            $participationMap = [];
            foreach ($participations as $p) {
                $key = $p->jeune_id . '_' . $p->activite_id;
                $participationMap[$key] = true;
            }
            
            // Vérifier que le jeune a une branche
            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom,
                        'age' => $jeune->age,
                        'photo' => $jeune->photo,
                        'branche' => null,
                        'etapes' => []
                    ],
                    'message' => 'Vous n\'êtes pas encore assigné à une branche'
                ], 200);
            }
            
            // Construire la réponse (MÊME STRUCTURE QUE getSuiviComplet)
            $resultat = [
                'id' => $jeune->id,
                'nom' => $jeune->nom,
                'age' => $jeune->age,
                'photo' => $jeune->photo,
                'branche' => [
                    'id' => $jeune->branche->id,
                    'nomBranche' => $jeune->branche->nomBranche,
                    'ordreBranche' => $jeune->branche->ordreBranche // ← AJOUT
                ],
                'etapes' => []
            ];
            
            foreach ($jeune->branche->etapes as $etape) {
                $etapeData = [
                    'id' => $etape->id,
                    'nom' => $etape->nom,
                    'numEtape' => $etape->numEtape,
                    'activites' => []
                ];
                
                // Trier les activités par date ou par nom si nécessaire
                $activites = $etape->activites->sortBy('nom_act');
                
                foreach ($activites as $activite) {
                    $key = $jeune->id . '_' . $activite->id;
                    $isParticipated = isset($participationMap[$key]);
                    
                    $etapeData['activites'][] = [
                        'id' => $activite->id,
                        'nom_act' => $activite->nom_act,
                        'description' => $activite->description,
                        'badge' => $activite->badge,
                        'date_debut' => $activite->date_debut,
                        'date_fin' => $activite->date_fin,
                        'is_participated' => $isParticipated
                    ];
                }
                
                // Ajouter toutes les étapes même celles qui n'ont pas d'activités
                $resultat['etapes'][] = $etapeData;
            }
            
            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Votre progression récupérée avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de votre progression',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * VOIR LES STATISTIQUES PERSONNELLES
     * Endpoint: GET /api/mes-statistiques
     */
    public function getMesStatistiques(Request $request)
    {
        try {
            $jeune = $request->user();
            
            // Charger les relations avec tri
            $jeune->load(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }, 'branche.etapes' => function($query) {
                $query->orderBy('numEtape');
            }, 'branche.etapes.activites']);
            
            // Vérifier que le jeune a une branche
            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_activites_branche' => 0,
                        'activites_validees' => 0,
                        'pourcentage_progression' => '0%',
                        'etapes' => []
                    ],
                    'message' => 'Vous n\'êtes pas encore assigné à une branche'
                ], 200);
            }
            
            $totalParticipations = Act_Jeune::where('jeune_id', $jeune->id)->count();
            
            $totalActivitesBranche = 0;
            foreach ($jeune->branche->etapes as $etape) {
                $totalActivitesBranche += $etape->activites->count();
            }
            
            $pourcentage = $totalActivitesBranche > 0 
                ? round(($totalParticipations / $totalActivitesBranche) * 100, 2)
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_activites_branche' => $totalActivitesBranche,
                    'activites_validees' => $totalParticipations,
                    'pourcentage_progression' => $pourcentage . '%',
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nomBranche' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche // ← AJOUT
                    ],
                    'etapes' => $jeune->branche->etapes->map(function($etape) use ($jeune) {
                        $activitesValidees = Act_Jeune::where('jeune_id', $jeune->id)
                            ->whereIn('activite_id', $etape->activites->pluck('id'))
                            ->count();
                        
                        return [
                            'id' => $etape->id,
                            'nom' => $etape->nom,
                            'numEtape' => $etape->numEtape,
                            'total_activites' => $etape->activites->count(),
                            'activites_validees' => $activitesValidees,
                            'est_complete' => $activitesValidees === $etape->activites->count() && $etape->activites->count() > 0
                        ];
                    })
                ],
                'message' => 'Statistiques récupérées avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}