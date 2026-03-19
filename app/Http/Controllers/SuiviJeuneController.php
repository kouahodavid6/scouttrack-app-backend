<?php

namespace App\Http\Controllers;

use App\Models\Act_Jeune;
use App\Models\CU;
use App\Models\Jeune;
use App\Models\Activite;
use App\Models\Etape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SuiviJeuneController extends Controller
{
    /**
     * RÉCUPÉRER LES JEUNES DU CHEF D'UNITÉ CONNECTÉ
     * Endpoint: GET /api/chef/mes-jeunes
     */
    public function getMesJeunes(Request $request)
    {
        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $jeunes = Jeune::with(['branche' => function ($query) {
                $query->orderBy('ordreBranche');
            }])
                ->where('cu_id', $chef->id)
                ->orderBy('nom')
                ->get()
                ->map(function ($jeune) {
                    return [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom,
                        'age' => $jeune->age,
                        'photo' => $jeune->photo,
                        'email' => $jeune->email,
                        'tel' => $jeune->tel,
                        'date_naissance' => $jeune->date_naissance,
                        'branche' => $jeune->branche ? [
                            'id' => $jeune->branche->id,
                            'nomBranche' => $jeune->branche->nomBranche,
                            'ordreBranche' => $jeune->branche->ordreBranche
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $jeunes,
                'message' => 'Jeunes récupérés avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des jeunes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SUIVI COMPLET D'UN JEUNE SPÉCIFIQUE
     * Endpoint: GET /api/suivi/jeune/{id}
     */
    public function getSuiviJeune(Request $request, $id)
    {
        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            // Vérifier que le jeune appartient au chef
            $jeune = Jeune::with(['branche'])
                ->where('id', $id)
                ->where('cu_id', $chef->id)
                ->first();

            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jeune introuvable ou ne vous appartient pas'
                ], 404);
            }

            if (!$jeune->branche) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune n\'a pas de branche assignée'
                ], 400);
            }

            // Récupérer toutes les étapes de la branche avec leurs activités (SANS .badge)
            $etapes = Etape::with(['activites']) // ← MODIFICATION ICI
                ->where('branche_id', $jeune->branche_id)
                ->orderBy('numEtape')
                ->get();

            // Récupérer les participations du jeune
            $participations = Act_Jeune::where('jeune_id', $jeune->id)->get();
            $participationMap = [];
            foreach ($participations as $p) {
                $participationMap[$p->activite_id] = [
                    'id' => $p->id,
                    'statut' => $p->statut,
                    'created_at' => $p->created_at
                ];
            }

            // Construire la réponse
            $resultat = [
                'jeune' => [
                    'id' => $jeune->id,
                    'nom' => $jeune->nom,
                    'age' => $jeune->age,
                    'photo' => $jeune->photo,
                    'email' => $jeune->email,
                    'tel' => $jeune->tel,
                    'date_naissance' => $jeune->date_naissance,
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nomBranche' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche,
                        'age_min' => $jeune->branche->age_min,
                        'age_max' => $jeune->branche->age_max
                    ]
                ],
                'progression' => [
                    'total_activites' => 0,
                    'activites_validees' => count($participations),
                    'pourcentage' => 0
                ],
                'etapes' => []
            ];

            $totalActivites = 0;

            foreach ($etapes as $etape) {
                $etapeData = [
                    'id' => $etape->id,
                    'nom' => $etape->nom,
                    'numEtape' => $etape->numEtape,
                    'description' => $etape->description,
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
                    }

                    // MODIFICATION : badge est maintenant une simple chaîne (URL)
                    $badgeData = null;
                    if ($activite->badge) {
                        $badgeData = [
                            'id' => $activite->id, // Utiliser l'ID de l'activité
                            'nom' => 'Badge ' . $activite->nom_act, // Nom générique
                            'image' => $activite->badge, // ← C'est l'URL stockée
                            'description' => $activite->description
                        ];
                    }

                    $etapeData['activites'][] = [
                        'id' => $activite->id,
                        'nom_act' => $activite->nom_act,
                        'description' => $activite->description,
                        'badge' => $badgeData,
                        'date_debut' => $activite->date_debut,
                        'date_fin' => $activite->date_fin,
                        'is_participated' => $isParticipated,
                        'participation' => $isParticipated ? $participationMap[$activite->id] : null
                    ];
                }

                $etapeData['activites_validees'] = $activitesValideesEtape;
                $etapeData['est_complete'] = ($activitesValideesEtape === $etapeData['total_activites']) && $etapeData['total_activites'] > 0;

                $resultat['etapes'][] = $etapeData;
            }

            $resultat['progression']['total_activites'] = $totalActivites;
            $resultat['progression']['pourcentage'] = $totalActivites > 0
                ? round(($resultat['progression']['activites_validees'] / $totalActivites) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Suivi du jeune récupéré avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SUIVI COMPLET : TOUS LES JEUNES AVEC LEUR PROGRESSION
     * Endpoint: GET /api/suivi/jeunes
     */
    public function getSuiviComplet(Request $request)
    {
        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $jeunes = Jeune::with(['branche'])
                ->where('cu_id', $chef->id)
                ->orderBy('nom')
                ->get();

            $resultat = [];

            foreach ($jeunes as $jeune) {
                if (!$jeune->branche) {
                    continue;
                }

                // Compter les participations
                $participations = Act_Jeune::where('jeune_id', $jeune->id)->count();

                // Compter le total d'activités dans sa branche
                $totalActivites = Activite::whereHas('etape', function ($query) use ($jeune) {
                    $query->where('branche_id', $jeune->branche_id);
                })->count();

                $pourcentage = $totalActivites > 0 ? round(($participations / $totalActivites) * 100, 2) : 0;

                $resultat[] = [
                    'id' => $jeune->id,
                    'nom' => $jeune->nom,
                    'age' => $jeune->age,
                    'photo' => $jeune->photo,
                    'branche' => [
                        'id' => $jeune->branche->id,
                        'nomBranche' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche
                    ],
                    'statistiques' => [
                        'total_activites' => $totalActivites,
                        'activites_validees' => $participations,
                        'pourcentage' => $pourcentage . '%'
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Suivi des jeunes récupéré avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * VALIDER LA PARTICIPATION D'UN JEUNE À UNE ACTIVITÉ
     * Endpoint: POST /api/suivi/valider
     */
    public function validerParticipation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id',
            'activite_id' => 'required|uuid|exists:activites,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $jeune = Jeune::where('id', $request->jeune_id)
                ->where('cu_id', $chef->id)
                ->first();

            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune ne vous appartient pas ou n\'existe pas'
                ], 403);
            }

            $participationExistante = Act_Jeune::where('jeune_id', $request->jeune_id)
                ->where('activite_id', $request->activite_id)
                ->first();

            if ($participationExistante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune a déjà validé cette activité'
                ], 409);
            }

            $participation = new Act_Jeune();
            $participation->jeune_id = $request->jeune_id;
            $participation->activite_id = $request->activite_id;
            $participation->statut = 'valide';
            $participation->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $participation->id,
                    'jeune_id' => $participation->jeune_id,
                    'activite_id' => $participation->activite_id,
                    'statut' => $participation->statut,
                    'created_at' => $participation->created_at
                ],
                'message' => 'Participation validée avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la participation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SUPPRIMER UNE PARTICIPATION
     * Endpoint: DELETE /api/suivi/invalider/{participation_id}
     */
    public function supprimerParticipation(Request $request, $participation_id)
    {
        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $actJeune = Act_Jeune::with('jeune')->find($participation_id);

            if (!$actJeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participation introuvable'
                ], 404);
            }

            // Vérifier que le jeune appartient au chef
            if ($actJeune->jeune->cu_id !== $chef->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à supprimer cette participation'
                ], 403);
            }

            $actJeune->delete();

            return response()->json([
                'success' => true,
                'message' => 'Participation supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la participation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * VÉRIFIER SI UNE ÉTAPE EST COMPLÈTE POUR UN JEUNE
     * Endpoint: GET /api/suivi/etape-complete
     */
    public function checkEtapeComplete(Request $request)
    {
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
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $jeune = Jeune::where('id', $request->jeune_id)
                ->where('cu_id', $chef->id)
                ->first();

            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce jeune ne vous appartient pas'
                ], 403);
            }

            $activites = Activite::where('etape_id', $request->etape_id)->get();
            $totalActivites = $activites->count();

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
                    'est_complete' => $estComplete
                ],
                'message' => $estComplete ? 'Toutes les activités sont validées' : 'Il reste des activités à valider'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * STATISTIQUES D'UN JEUNE
     * Endpoint: GET /api/suivi/jeune/{id}/statistiques
     */
    public function getStatistiquesJeune(Request $request, $id)
    {
        try {
            $user = $request->user();
            $chef = CU::find($user->id);

            if (!$chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef d\'unité introuvable'
                ], 404);
            }

            $jeune = Jeune::with(['branche'])
                ->where('id', $id)
                ->where('cu_id', $chef->id)
                ->first();

            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jeune introuvable ou ne vous appartient pas'
                ], 404);
            }

            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'jeune' => [
                            'id' => $jeune->id,
                            'nom' => $jeune->nom
                        ],
                        'statistiques' => [
                            'total_activites' => 0,
                            'activites_validees' => 0,
                            'pourcentage' => '0%'
                        ]
                    ],
                    'message' => 'Ce jeune n\'est pas assigné à une branche'
                ], 200);
            }

            $totalParticipations = Act_Jeune::where('jeune_id', $id)->count();

            $totalActivites = Activite::whereHas('etape', function ($query) use ($jeune) {
                $query->where('branche_id', $jeune->branche_id);
            })->count();

            $pourcentage = $totalActivites > 0
                ? round(($totalParticipations / $totalActivites) * 100, 2)
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
                        'nomBranche' => $jeune->branche->nomBranche,
                        'ordreBranche' => $jeune->branche->ordreBranche
                    ],
                    'statistiques' => [
                        'total_activites' => $totalActivites,
                        'activites_validees' => $totalParticipations,
                        'pourcentage' => $pourcentage . '%'
                    ]
                ],
                'message' => 'Statistiques récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}