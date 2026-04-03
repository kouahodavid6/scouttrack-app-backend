<?php

namespace App\Http\Controllers;

use App\Models\Parents as ParentModel;
use App\Models\Jeune;
use App\Models\Cotisation;
use App\Models\Paiement;
use App\Models\ActiviteSpeciale;
use App\Models\Presence;
use App\Models\Reunion;
use App\Models\Act_Jeune;
use App\Models\Etape;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ParentController extends Controller
{
    public function getEnfants(Request $request)
    {
        try {
            $parent = $request->user();
            
            $enfants = $parent->enfants()
                ->with(['branche', 'cu'])
                ->get()
                ->map(function ($enfant) {
                    return [
                        'id' => $enfant->id,
                        'nom' => $enfant->nom,
                        'age' => $enfant->age,
                        'photo' => $enfant->photo,
                        'branche' => $enfant->branche ? [
                            'id' => $enfant->branche->id,
                            'nom' => $enfant->branche->nomBranche
                        ] : null,
                        'lien' => $enfant->pivot->lien,
                        'autorisation_camp' => (bool) $enfant->pivot->autorisation_camp,
                        'autorisations' => json_decode($enfant->pivot->autorisations, true) ?? []
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $enfants,
                'message' => 'Enfants récupérés avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enfants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProgressionEnfant(Request $request, $id)
    {
        try {
            $parent = $request->user();
            
            if (!$parent->hasEnfant($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $jeune = Jeune::with(['branche'])->find($id);
            
            if (!$jeune) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enfant non trouvé'
                ], 404);
            }

            if (!$jeune->branche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'jeune' => [
                            'id' => $jeune->id,
                            'nom' => $jeune->nom,
                            'age' => $jeune->age,
                            'photo' => $jeune->photo
                        ],
                        'progression' => null,
                        'message' => 'Cet enfant n\'est pas encore assigné à une branche'
                    ]
                ], 200);
            }

            $etapes = Etape::with(['activites'])
                ->where('branche_id', $jeune->branche_id)
                ->orderBy('numEtape')
                ->get();

            $participations = Act_Jeune::where('jeune_id', $jeune->id)->get();
            $participationMap = [];
            foreach ($participations as $p) {
                $participationMap[$p->activite_id] = true;
            }

            $totalActivites = 0;
            $activitesValidees = 0;
            $etapesData = [];

            foreach ($etapes as $etape) {
                $etapeData = [
                    'id' => $etape->id,
                    'nom' => $etape->nom,
                    'numEtape' => $etape->numEtape,
                    'total_activites' => $etape->activites->count(),
                    'activites_validees' => 0,
                    'activites' => []
                ];

                $activitesValideesEtape = 0;

                foreach ($etape->activites as $activite) {
                    $totalActivites++;
                    $isValidee = isset($participationMap[$activite->id]);
                    if ($isValidee) {
                        $activitesValideesEtape++;
                        $activitesValidees++;
                    }

                    $etapeData['activites'][] = [
                        'id' => $activite->id,
                        'nom' => $activite->nom_act,
                        'description' => $activite->description,
                        'badge' => $activite->badge,
                        'is_validee' => $isValidee
                    ];
                }

                $etapeData['activites_validees'] = $activitesValideesEtape;
                $etapeData['est_complete'] = ($activitesValideesEtape === $etapeData['total_activites']) && $etapeData['total_activites'] > 0;
                $etapesData[] = $etapeData;
            }

            $pourcentage = $totalActivites > 0 ? round(($activitesValidees / $totalActivites) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'jeune' => [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom,
                        'age' => $jeune->age,
                        'photo' => $jeune->photo,
                        'branche' => $jeune->branche->nomBranche
                    ],
                    'statistiques' => [
                        'total_activites' => $totalActivites,
                        'activites_validees' => $activitesValidees,
                        'pourcentage' => $pourcentage . '%'
                    ],
                    'etapes' => $etapesData
                ],
                'message' => 'Progression récupérée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la progression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getReunionsEnfant(Request $request, $id)
    {
        try {
            $parent = $request->user();
            
            if (!$parent->hasEnfant($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $jeune = Jeune::find($id);
            if (!$jeune || !$jeune->cu_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucune réunion trouvée'
                ], 200);
            }

            $reunions = Reunion::with('presences')
                ->where('cu_id', $jeune->cu_id)
                ->orderBy('date_reunion', 'desc')
                ->get()
                ->map(function ($reunion) use ($jeune) {
                    $presence = $reunion->presences->where('jeune_id', $jeune->id)->first();
                    return [
                        'id' => $reunion->id,
                        'date_reunion' => $reunion->date_reunion,
                        'heure_debut' => $reunion->heure_debut,
                        'heure_fin' => $reunion->heure_fin,
                        'is_present' => $presence ? true : false,
                        'presence_date' => $presence ? $presence->created_at : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $reunions,
                'message' => 'Réunions récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réunions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCotisationsEnfant(Request $request, $id)
    {
        try {
            $parent = $request->user();
            
            if (!$parent->hasEnfant($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $cotisations = Cotisation::where('statut', 'active')
                ->with(['paiements' => function($query) use ($id) {
                    $query->where('jeune_id', $id);
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($cotisation) use ($id) {
                    $paiement = $cotisation->paiements->first();
                    return [
                        'id' => $cotisation->id,
                        'nom' => $cotisation->nom,
                        'description' => $cotisation->description,
                        'montant' => $cotisation->montant,
                        'montant_formatted' => $cotisation->montant_formatted,
                        'type' => $cotisation->type,
                        'date_limite' => $cotisation->date_limite,
                        'a_paye' => $paiement && $paiement->statut === 'paye',
                        'paiement' => $paiement ? [
                            'id' => $paiement->id,
                            'montant' => $paiement->montant,
                            'date_paiement' => $paiement->date_paiement,
                            'reference' => $paiement->reference
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $cotisations,
                'message' => 'Cotisations récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cotisations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function payerCotisation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id',
            'cotisation_id' => 'required|uuid|exists:cotisations,id',
            'telephone' => 'required|string|regex:/^[0-9]{8,13}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $parent = $request->user();
            
            if (!$parent->hasEnfant($request->jeune_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $jeune = Jeune::find($request->jeune_id);
            $cotisation = Cotisation::find($request->cotisation_id);

            if (!$cotisation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotisation non trouvée'
                ], 404);
            }

            $dejaPaye = Paiement::where('cotisation_id', $cotisation->id)
                ->where('jeune_id', $jeune->id)
                ->where('statut', 'paye')
                ->exists();

            if ($dejaPaye) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette cotisation a déjà été payée pour cet enfant'
                ], 400);
            }

            $paiement = Paiement::create([
                'cotisation_id' => $cotisation->id,
                'jeune_id' => $jeune->id,
                'jeune_nom' => $jeune->nom,
                'jeune_email' => $jeune->email,
                'montant' => $cotisation->montant,
                'numero_telephone' => $request->telephone,
                'statut' => 'en_attente',
                'transaction_id' => 'pending_' . uniqid()
            ]);

            $kkiapayConfig = [
                'public_key' => env('KKIAPAY_PUBLIC_KEY'),
                'secret_key' => env('KKIAPAY_SECRET_KEY'),
                'sandbox' => env('KKIAPAY_SANDBOX', true)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'paiement_id' => $paiement->id,
                    'montant' => $cotisation->montant,
                    'montant_formatted' => $cotisation->montant_formatted,
                    'nom_cotisation' => $cotisation->nom,
                    'jeune_nom' => $jeune->nom,
                    'telephone' => $request->telephone,
                    'kkiapay_config' => $kkiapayConfig
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaiements(Request $request)
    {
        try {
            $parent = $request->user();
            $enfantsIds = $parent->enfants()->pluck('jeunes.id')->toArray();

            $paiements = Paiement::with('cotisation')
                ->whereIn('jeune_id', $enfantsIds)
                ->where('statut', 'paye')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($paiement) {
                    return [
                        'id' => $paiement->id,
                        'jeune_nom' => $paiement->jeune_nom,
                        'cotisation_nom' => $paiement->cotisation->nom,
                        'montant' => $paiement->montant,
                        'montant_formatted' => $paiement->montant_formatted,
                        'reference' => $paiement->reference,
                        'date_paiement' => $paiement->date_paiement
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $paiements,
                'message' => 'Historique des paiements récupéré'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAutorisations(Request $request)
    {
        try {
            $parent = $request->user();
            $autorisations = [];

            foreach ($parent->enfants as $enfant) {
                $enfantAutorisations = [
                    'jeune_id' => $enfant->id,
                    'jeune_nom' => $enfant->nom,
                    'autorisation_camp' => (bool) $enfant->pivot->autorisation_camp,
                    'autorisations' => json_decode($enfant->pivot->autorisations, true) ?? []
                ];
                $autorisations[] = $enfantAutorisations;
            }

            return response()->json([
                'success' => true,
                'data' => $autorisations,
                'message' => 'Autorisations récupérées avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des autorisations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function donnerAutorisation(Request $request, $activiteId)
    {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $parent = $request->user();
            $activite = ActiviteSpeciale::find($activiteId);

            if (!$activite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activité non trouvée'
                ], 404);
            }

            if (!$parent->hasEnfant($request->jeune_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $relation = $parent->enfants()->where('jeune_id', $request->jeune_id)->first();

            if (!$relation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Relation parent-enfant non trouvée'
                ], 404);
            }

            $autorisations = json_decode($relation->pivot->autorisations, true) ?? [];

            if ($activite->type === 'camp') {
                $relation->pivot->autorisation_camp = true;
            }

            $autorisations[$activiteId] = [
                'activite_nom' => $activite->nom,
                'date_autorisation' => now()->toISOString(),
                'type' => $activite->type
            ];

            $relation->pivot->autorisations = json_encode($autorisations);
            $relation->pivot->save();

            return response()->json([
                'success' => true,
                'message' => 'Autorisation donnée avec succès',
                'data' => [
                    'activite_id' => $activiteId,
                    'autorisation_camp' => (bool) $relation->pivot->autorisation_camp,
                    'autorisations' => $autorisations
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'autorisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}