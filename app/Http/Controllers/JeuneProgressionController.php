<?php

namespace App\Http\Controllers;

use App\Models\Act_Jeune;
use Illuminate\Http\Request;

class JeuneProgressionController extends Controller
{
    /**
     * RÉCUPÉRER LA PROGRESSION DU JEUNE CONNECTÉ
     * Endpoint: GET /api/mon-suivi
     */
    public function getMaProgression(Request $request)
    {
        try {
            $jeune = $request->user();
            
            // Charger les relations
            $jeune->load(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }, 'branche.etapes' => function($query) {
                $query->orderBy('numEtape');
            }, 'branche.etapes.activites']);
            
            // Récupérer les participations
            $participations = Act_Jeune::where('jeune_id', $jeune->id)->get();
            
            // Construire le tableau de participation
            $participationMap = [];
            $participationDetails = [];
            foreach ($participations as $p) {
                $participationMap[$p->activite_id] = true;
                $participationDetails[$p->activite_id] = [
                    'id' => $p->id,
                    'statut' => $p->statut,
                    'created_at' => $p->created_at
                ];
            }
            
            // Vérifier que le jeune a une branche
            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'jeune' => [
                            'id' => $jeune->id,
                            'nom' => $jeune->nom ?? '',
                            'age' => $jeune->age,
                            'photo' => $jeune->photo,
                            'email' => $jeune->email ?? '',
                            'tel' => $jeune->tel ?? '',
                        ],
                        'branche' => null,
                        'etapes' => [],
                        'statistiques' => [
                            'total_activites' => 0,
                            'activites_validees' => 0,
                            'pourcentage' => 0,
                            'badges_obtenus' => 0,
                            'total_badges' => 0
                        ]
                    ],
                    'message' => 'Vous n\'êtes pas encore assigné à une branche'
                ], 200);
            }
            
            // Calculer les statistiques
            $totalActivites = 0;
            $totalBadges = 0;
            $badgesObtenus = 0;
            $activitesValidees = 0;
            
            $etapesData = [];
            
            foreach ($jeune->branche->etapes as $etape) {
                $etapeData = [
                    'id' => $etape->id,
                    'nom' => $etape->nom,
                    'numEtape' => $etape->numEtape,
                    'total_activites' => $etape->activites->count(),
                    'activites_validees' => 0,
                    'est_complete' => false,
                    'activites' => []
                ];
                
                $activitesValideesEtape = 0;
                
                foreach ($etape->activites as $activite) {
                    $totalActivites++;
                    $isParticipated = isset($participationMap[$activite->id]);
                    
                    if ($isParticipated) {
                        $activitesValideesEtape++;
                        $activitesValidees++;
                    }
                    
                    // Gestion des badges
                    $badgeData = null;
                    if ($activite->badge) {
                        $totalBadges++;
                        if ($isParticipated) {
                            $badgesObtenus++;
                        }
                        $badgeData = [
                            'id' => $activite->id,
                            'nom' => $activite->nom_act,
                            'image' => $activite->badge,
                            'obtenu' => $isParticipated,
                            'date_obtention' => $isParticipated ? ($participationDetails[$activite->id]['created_at'] ?? null) : null
                        ];
                    }
                    
                    $etapeData['activites'][] = [
                        'id' => $activite->id,
                        'nom' => $activite->nom_act,
                        'description' => $activite->description,
                        'badge' => $badgeData,
                        'date_debut' => $activite->date_debut,
                        'date_fin' => $activite->date_fin,
                        'is_participated' => $isParticipated,
                        'participation' => $isParticipated ? ($participationDetails[$activite->id] ?? null) : null
                    ];
                }
                
                $etapeData['activites_validees'] = $activitesValideesEtape;
                $etapeData['est_complete'] = ($activitesValideesEtape === $etapeData['total_activites']) && $etapeData['total_activites'] > 0;
                
                $etapesData[] = $etapeData;
            }
            
            $pourcentage = $totalActivites > 0 ? round(($activitesValidees / $totalActivites) * 100, 2) : 0;
            
            $resultat = [
                'jeune' => [
                    'id' => $jeune->id,
                    'nom' => $jeune->nom ?? '',
                    'age' => $jeune->age,
                    'photo' => $jeune->photo,
                    'email' => $jeune->email ?? '',
                    'tel' => $jeune->tel ?? '',
                ],
                'branche' => [
                    'id' => $jeune->branche->id,
                    'nom' => $jeune->branche->nomBranche,
                    'ordre' => $jeune->branche->ordreBranche
                ],
                'statistiques' => [
                    'total_activites' => $totalActivites,
                    'activites_validees' => $activitesValidees,
                    'pourcentage' => $pourcentage,
                    'badges_obtenus' => $badgesObtenus,
                    'total_badges' => $totalBadges
                ],
                'etapes' => $etapesData
            ];
            
            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Votre progression récupérée avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de votre progression',
                'error' => $e->getMessage()
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
            
            $jeune->load(['branche' => function($query) {
                $query->orderBy('ordreBranche');
            }, 'branche.etapes' => function($query) {
                $query->orderBy('numEtape');
            }, 'branche.etapes.activites']);
            
            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_activites' => 0,
                        'activites_validees' => 0,
                        'pourcentage' => 0,
                        'badges_obtenus' => 0,
                        'total_badges' => 0,
                        'etapes' => []
                    ],
                    'message' => 'Vous n\'êtes pas encore assigné à une branche'
                ], 200);
            }
            
            $totalParticipations = Act_Jeune::where('jeune_id', $jeune->id)->count();
            
            $totalActivites = 0;
            $totalBadges = 0;
            $badgesObtenus = 0;
            
            foreach ($jeune->branche->etapes as $etape) {
                foreach ($etape->activites as $activite) {
                    $totalActivites++;
                    if ($activite->badge) {
                        $totalBadges++;
                    }
                }
            }
            
            // Compter les badges obtenus
            $participations = Act_Jeune::where('jeune_id', $jeune->id)
                ->with('activite')
                ->get();
            
            foreach ($participations as $participation) {
                if ($participation->activite && $participation->activite->badge) {
                    $badgesObtenus++;
                }
            }
            
            $pourcentage = $totalActivites > 0 ? round(($totalParticipations / $totalActivites) * 100, 2) : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_activites' => $totalActivites,
                    'activites_validees' => $totalParticipations,
                    'pourcentage' => $pourcentage,
                    'badges_obtenus' => $badgesObtenus,
                    'total_badges' => $totalBadges,
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nom' => $jeune->branche->nomBranche,
                        'ordre' => $jeune->branche->ordreBranche
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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RÉCUPÉRER LES BADGES OBTENUS PAR LE JEUNE
     * Endpoint: GET /api/mes-badges
     */
    public function getMesBadges(Request $request)
    {
        try {
            $jeune = $request->user();
            
            // Récupérer toutes les participations avec leurs activités
            $participations = Act_Jeune::where('jeune_id', $jeune->id)
                ->with('activite')
                ->get();
            
            // Filtrer uniquement les activités qui ont un badge
            $badges = [];
            
            foreach ($participations as $participation) {
                $activite = $participation->activite;
                
                // Vérifier si l'activité a un badge
                if ($activite && $activite->badge) {
                    $badges[] = [
                        'id' => $activite->id,
                        'nom' => $activite->nom_act,
                        'image' => $activite->badge,
                        'date_obtention' => $participation->created_at,
                        'etape_nom' => $activite->etape ? $activite->etape->nom : null
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'badges' => $badges,
                    'total' => count($badges),
                    'jeune' => [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom ?? '',
                        'photo' => $jeune->photo
                    ]
                ],
                'message' => 'Badges récupérés avec succès'
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des badges',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}