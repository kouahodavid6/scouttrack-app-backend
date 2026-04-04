<?php

namespace App\Http\Controllers;

use App\Models\DemandeAutorisation;
use App\Models\ReponseAutorisation;
use App\Models\Jeune;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AutorisationController extends Controller
{
    /**
     * CU : Récupérer toutes les demandes envoyées
     */
    public function getMesDemandes(Request $request)
    {
        try {
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $demandes = DemandeAutorisation::where('cu_id', $cu->id)
                ->with(['reponses' => function($query) {
                    $query->with(['jeune', 'parent']);
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($demande) use ($cu) {
                    $totalJeunes = Jeune::where('cu_id', $cu->id)->count();
                    $jeunesAyantRepondu = $demande->reponses()
                        ->distinct('jeune_id')
                        ->count('jeune_id');
                    $estComplete = $totalJeunes > 0 && $jeunesAyantRepondu >= $totalJeunes;
                    
                    if ($estComplete && $demande->status !== 'terminee') {
                        $demande->status = 'terminee';
                        $demande->save();
                    }
                    
                    return [
                        'id' => $demande->id,
                        'titre' => $demande->titre,
                        'description' => $demande->description,
                        'date_activite' => $demande->date_activite,
                        'lieu' => $demande->lieu,
                        'status' => $demande->status,
                        'est_complete' => $estComplete,
                        'created_at' => $demande->created_at,
                        'reponses' => $demande->reponses->map(function ($reponse) {
                            return [
                                'id' => $reponse->id,
                                'reponse' => $reponse->reponse,
                                'jeune' => $reponse->jeune ? [
                                    'id' => $reponse->jeune->id,
                                    'nom' => $reponse->jeune->nom
                                ] : null,
                                'parent' => $reponse->parent ? [
                                    'id' => $reponse->parent->id,
                                    'nom' => $reponse->parent->nom,
                                    'email' => $reponse->parent->email,
                                    'tel' => $reponse->parent->tel
                                ] : null,
                                'donnees_formulaire' => $reponse->donnees_formulaire,
                                'signature' => $reponse->signature,
                                'created_at' => $reponse->created_at
                            ];
                        }),
                        'taux_participation' => $demande->taux_participation
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $demandes
            ]);

        } catch (\Exception $e) {
            Log::error('getMesDemandes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * CU : Supprimer une demande
     */
    public function supprimerDemande(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $demande = DemandeAutorisation::where('cu_id', $cu->id)->find($id);

            if (!$demande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            $demande->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('supprimerDemande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * CU : Récupérer les réponses d'une demande
     */
    public function getReponses(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $demande = DemandeAutorisation::where('cu_id', $cu->id)->find($id);

            if (!$demande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            $reponses = $demande->reponses()->with(['jeune', 'parent'])->get()->map(function ($reponse) {
                return [
                    'id' => $reponse->id,
                    'reponse' => $reponse->reponse,
                    'jeune' => $reponse->jeune ? [
                        'id' => $reponse->jeune->id,
                        'nom' => $reponse->jeune->nom
                    ] : null,
                    'parent' => $reponse->parent ? [
                        'id' => $reponse->parent->id,
                        'nom' => $reponse->parent->nom,
                        'email' => $reponse->parent->email,
                        'tel' => $reponse->parent->tel
                    ] : null,
                    'donnees_formulaire' => $reponse->donnees_formulaire,
                    'signature' => $reponse->signature,
                    'created_at' => $reponse->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $reponses
            ]);

        } catch (\Exception $e) {
            Log::error('getReponses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * CU : Envoyer une demande d'autorisation
     */
    public function envoyerDemande(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_activite' => 'required|date',
            'lieu' => 'required|string|max:255',
            'texte_trous' => 'nullable|array'
        ]);

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
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $demande = DemandeAutorisation::create([
                'cu_id' => $cu->id,
                'titre' => $request->titre,
                'description' => $request->description,
                'date_activite' => $request->date_activite,
                'lieu' => $request->lieu,
                'texte_trous' => $request->texte_trous ?? $this->getDefaultTexteTrous(),
                'status' => 'en_attente'
            ]);

            return response()->json([
                'success' => true,
                'data' => $demande,
                'message' => 'Demande envoyée avec succès'
            ], 201);

        } catch (\Exception $e) {
            Log::error('envoyerDemande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Parent : Récupérer les demandes en attente pour ses enfants
     */
    public function getDemandesParent(Request $request)
    {
        try {
            $parent = $request->user();
            $enfants = $parent->enfants()->pluck('jeunes.id')->toArray();

            $demandes = DemandeAutorisation::where('status', 'en_attente')
                ->with(['reponses' => function($query) use ($parent) {
                    $query->where('parent_id', $parent->id);
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            $demandesArray = $demandes->map(function($demande) use ($enfants) {
                $reponsesParEnfant = [];
                foreach ($enfants as $jeuneId) {
                    $reponse = $demande->reponses->where('jeune_id', $jeuneId)->first();
                    $reponsesParEnfant[$jeuneId] = $reponse ? [
                        'id' => $reponse->id,
                        'reponse' => $reponse->reponse,
                        'donnees_formulaire' => $reponse->donnees_formulaire,
                        'created_at' => $reponse->created_at
                    ] : null;
                }
                
                return [
                    'id' => $demande->id,
                    'titre' => $demande->titre,
                    'description' => $demande->description,
                    'date_activite' => $demande->date_activite,
                    'lieu' => $demande->lieu,
                    'status' => $demande->status,
                    'created_at' => $demande->created_at,
                    'reponses_par_enfant' => $reponsesParEnfant
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $demandesArray
            ]);

        } catch (\Exception $e) {
            Log::error('getDemandesParent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Parent : Récupérer l'historique des réponses
     */
    public function getHistoriqueReponses(Request $request)
    {
        try {
            $parent = $request->user();
            
            $reponses = ReponseAutorisation::where('parent_id', $parent->id)
                ->with(['demande', 'jeune'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($reponse) {
                    return [
                        'id' => $reponse->id,
                        'demande_id' => $reponse->demande_id,
                        'demande_titre' => $reponse->demande->titre ?? 'Titre inconnu',
                        'demande_date_activite' => $reponse->demande->date_activite ?? null,
                        'demande_lieu' => $reponse->demande->lieu ?? null,
                        'jeune_nom' => $reponse->jeune->nom ?? 'Jeune inconnu',
                        'reponse' => $reponse->reponse,
                        'signature' => $reponse->signature,
                        'repondu_le' => $reponse->created_at,
                        'donnees_formulaire' => $reponse->donnees_formulaire,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $reponses
            ]);

        } catch (\Exception $e) {
            Log::error('getHistoriqueReponses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Parent : Répondre à une demande
     */
    public function repondreDemande(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jeune_id' => 'required|uuid|exists:jeunes,id',
            'reponse' => 'required|in:oui,non',
            'donnees_formulaire' => 'nullable|array',
            'signature' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $parent = $request->user();
            $demande = DemandeAutorisation::lockForUpdate()->find($id);

            if (!$demande) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            if (!$parent->hasEnfant($request->jeune_id)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à cet enfant'
                ], 403);
            }

            $existingReponse = ReponseAutorisation::where('demande_id', $id)
                ->where('jeune_id', $request->jeune_id)
                ->first();

            if ($existingReponse) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Une réponse a déjà été donnée pour cet enfant'
                ], 400);
            }

            $reponse = ReponseAutorisation::create([
                'demande_id' => $id,
                'parent_id' => $parent->id,
                'jeune_id' => $request->jeune_id,
                'reponse' => $request->reponse,
                'donnees_formulaire' => $request->donnees_formulaire,
                'signature' => $request->signature
            ]);

            $demande->updateStatusIfComplete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $reponse,
                'demande_status' => $demande->fresh()->status,
                'message' => 'Réponse enregistrée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('repondreDemande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    private function getDefaultTexteTrous()
    {
        return [
            'template' => "Je soussigné(e) [NOM_PARENT], en qualité de [LIEN_PARENT], autorise mon enfant [NOM_ENFANT] à participer à l'activité \"[TITRE_ACTIVITE]\". Fait à [VILLE], le [DATE_JOUR].",
            'fields' => [
                'NOM_PARENT' => ['type' => 'text', 'label' => 'Nom du parent', 'required' => true],
                'LIEN_PARENT' => ['type' => 'select', 'label' => 'Lien de parenté', 'options' => ['Père', 'Mère', 'Tuteur'], 'required' => true],
                'NOM_ENFANT' => ['type' => 'text', 'label' => 'Nom de l\'enfant', 'required' => true],
                'TITRE_ACTIVITE' => ['type' => 'text', 'label' => 'Titre de l\'activité', 'required' => true],
                'VILLE' => ['type' => 'text', 'label' => 'Ville', 'required' => true],
                'DATE_JOUR' => ['type' => 'date', 'label' => 'Date du jour', 'required' => true]
            ]
        ];
    }
}