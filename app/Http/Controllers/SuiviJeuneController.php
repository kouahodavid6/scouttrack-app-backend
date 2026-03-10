<?php

namespace App\Http\Controllers;

use App\Models\Act_Jeune;
use App\Models\CU;
use App\Models\Jeune;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuiviJeuneController extends Controller
{
    /**
     * RÉCUPÉRER LES JEUNES DU CHEF D'UNITÉ CONNECTÉ
     * Endpoint: GET /api/chef/mes-jeunes
     */
    public function getMesJeunes (Request $request) {
        try {
            // Récupérer l'utilisateur connecté (qui est un chef d'unité)
            $user = $request->user();
            
            // Trouver le chef d'unité correspondant
            $chef = CU::find($user->id);
            
            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }
            
            // Récupérer tous les jeunes de ce chef avec leur branche (triée par ordre)
            $jeunes = Jeune::with(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }])
                ->where('cu_id', $chef->id)
                ->orderBy('nom')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $jeunes,
                'message' => 'Jeunes récupérés avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des jeunes',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SUIVI COMPLET : ACTIVITÉS PAR ÉTAPE AVEC STATUT DE PARTICIPATION
     * Endpoint: GET /api/suivi/jeunes
     */
    public function getSuiviComplet (Request $request) {
        try {
            // 1. Récupérer le chef connecté
            $user = $request->user();
            $chef = CU::find($user->id);
            
            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }
            
            // 2. Récupérer tous les jeunes du chef avec leurs relations (triées)
            $jeunes = Jeune::with([
                'branche' => function($query) {
                    $query->orderBy('ordreBranche');
                }, 
                'branche.etapes' => function($query) {
                    $query->orderBy('numEtape');
                }, 
                'branche.etapes.activites'
            ])
                ->where('cu_id', $chef->id)
                ->orderBy('nom')
                ->get();
            
            // 3. Récupérer toutes les participations (act_jeune) pour ces jeunes
            $jeuneIds = $jeunes->pluck('id');
            $participations = Act_Jeune::whereIn('jeune_id', $jeuneIds)->get();
            
            // 4. Construire un tableau de participation pour un accès rapide
            $participationMap = [];
            foreach ($participations as $p) {
                $key = $p->jeune_id . '_' . $p->activite_id;
                $participationMap[$key] = true;
            }
            
            // 5. Construire la réponse structurée
            $resultat = [];
            
            foreach ($jeunes as $jeune) {
                // Sécurité : si le jeune n'a pas de branche, on l'ignore
                if (!$jeune->branche) {
                    continue;
                }
                
                $jeuneData = [
                    'id' => $jeune->id,
                    'nom' => $jeune->nom,
                    'age' => $jeune->age,
                    'photo' => $jeune->photo,
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nom' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche // ← AJOUT
                    ],
                    'etapes' => []
                ];
                
                // Parcourir les étapes de la branche du jeune (déjà triées par numEtape)
                foreach ($jeune->branche->etapes as $etape) {
                    $etapeData = [
                        'id' => $etape->id,
                        'nom' => $etape->nom,
                        'numEtape' => $etape->numEtape,
                        'activites' => []
                    ];
                    
                    // Trier les activités par nom
                    $activites = $etape->activites->sortBy('nom_act');
                    
                    // Parcourir les activités de l'étape
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
                    $jeuneData['etapes'][] = $etapeData;
                }
                
                $resultat[] = $jeuneData;
            }
            
            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Suivi des activités récupéré avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * VALIDER LA PARTICIPATION D'UN JEUNE À UNE ACTIVITÉ
     * Endpoint: POST /api/suivi/valider
     */
    public function validerParticipation (Request $request) {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id',
            'activite_id' => 'required|uuid|exists:activites,id',
            'statut' => 'nullable|string|in:en_cours,valide,echoue'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Vérifier que le jeune appartient bien au chef connecté (sécurité)
            $user = $request->user();
            $chef = CU::find($user->id);
            
            $jeune = Jeune::where('id', $request->jeune_id)
                ->where('cu_id', $chef->id)
                ->first();
                
            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune ne vous appartient pas ou n\'existe pas'
                ], 403);
            }
            
            // Vérifier si la participation existe déjà
            $participationExistante = Act_Jeune::where('jeune_id', $request->jeune_id)
                ->where('activite_id', $request->activite_id)
                ->first();
                
            if ($participationExistante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune a déjà validé cette activité'
                ], 409);
            }
            
            // Créer la participation
            $participation = new Act_Jeune();
            $participation->jeune_id = $request->jeune_id;
            $participation->activite_id = $request->activite_id;
            $participation->statut = $request->statut ?? 'valide';
            $participation->save();
            
            // Charger les relations pour la réponse
            $participation->load(['jeune', 'activite']);
            
            return response()->json([
                'success' => true,
                'data' => $participation,
                'message' => 'Participation validée avec succès. Le badge peut être attribué.'
            ], 201);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la participation',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * VÉRIFIER SI UNE ÉTAPE EST COMPLÈTE POUR UN JEUNE
     * Endpoint: GET /api/suivi/etape-complete
     */
    public function checkEtapeComplete (Request $request) {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id',
            'etape_id' => 'required|uuid|exists:etapes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Vérifier que le jeune appartient au chef connecté
            $user = $request->user();
            $chef = CU::find($user->id);
            
            $jeune = Jeune::where('id', $request->jeune_id)
                ->where('cu_id', $chef->id)
                ->first();
                
            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune ne vous appartient pas'
                ], 403);
            }
            
            // Récupérer toutes les activités de l'étape
            $activites = Activite::where('etape_id', $request->etape_id)->get();
            $totalActivites = $activites->count();
            
            // Récupérer les participations du jeune pour ces activités
            $participations = Act_Jeune::where('jeune_id', $request->jeune_id)
                ->whereIn('activite_id', $activites->pluck('id'))
                ->count();
                
            $estComplete = ($participations === $totalActivites) && ($totalActivites > 0);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'jeune_id' => $request->jeune_id,
                    'etape_id' => $request->etape_id,
                    'total_activites' => $totalActivites,
                    'activites_validees' => $participations,
                    'est_complete' => $estComplete,
                    'peut_passer_etape_suivante' => $estComplete
                ],
                'message' => $estComplete ? 'Toutes les activités sont validées' : 'Il reste des activités à valider'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * OPTIONNEL: RÉCUPÉRER LES STATISTIQUES D'UN JEUNE
     * Endpoint: GET /api/suivi/jeune/{id}/statistiques
     */
    public function getStatistiquesJeune (Request $request, $id) {
        try {
            // Vérifier que le jeune appartient au chef connecté
            $user = $request->user();
            $chef = CU::find($user->id);
            
            $jeune = Jeune::with(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }, 'branche.etapes' => function($query) {
                $query->orderBy('numEtape');
            }, 'branche.etapes.activites'])
                ->where('id', $id)
                ->where('cu_id', $chef->id)
                ->first();
                
            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jeune introuvable ou ne vous appartient pas'
                ], 404);
            }
            
            // Vérifier que le jeune a une branche
            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'jeune' => [
                            'id' => $jeune->id,
                            'nom' => $jeune->nom
                        ],
                        'statistiques' => [
                            'total_activites_branche' => 0,
                            'activites_validees' => 0,
                            'pourcentage_progression' => '0%'
                        ]
                    ],
                    'message' => 'Ce jeune n\'est pas assigné à une branche'
                ], 200);
            }
            
            // Compter les participations
            $totalParticipations = Act_Jeune::where('jeune_id', $id)->count();
            
            // Compter le nombre total d'activités dans sa branche
            $totalActivitesBranche = 0;
            foreach ($jeune->branche->etapes as $etape) {
                $totalActivitesBranche += $etape->activites->count();
            }
            
            // Calculer le pourcentage de progression
            $pourcentage = $totalActivitesBranche > 0 
                ? round(($totalParticipations / $totalActivitesBranche) * 100, 2)
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'jeune' => [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom
                    ],
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nom' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche // ← AJOUT
                    ],
                    'statistiques' => [
                        'total_activites_branche' => $totalActivitesBranche,
                        'activites_validees' => $totalParticipations,
                        'pourcentage_progression' => $pourcentage . '%'
                    ]
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