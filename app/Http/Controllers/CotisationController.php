<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\Paiement;
use App\Models\Jeune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class CotisationController extends Controller
{
    // Liste des cotisations pour CU
    public function getCotisationsCU(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le nombre total de jeunes dans l'unité du CU
            $totalJeunes = Jeune::where('cu_id', $user->id)->count();
            
            $cotisations = Cotisation::where('created_by_type', $user->getTable())
                ->where('created_by_id', $user->id)
                ->with(['paiements' => function($query) {
                    $query->with('jeune')->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            // Ajouter les statistiques calculées
            $cotisations->each(function($cotisation) use ($totalJeunes) {
                $paiementsPayes = $cotisation->paiements->where('statut', 'paye');
                $nombrePayeurs = $paiementsPayes->count();
                $totalCollecte = $paiementsPayes->sum('montant');
                
                $cotisation->nombre_payeurs = $nombrePayeurs;
                $cotisation->total_jeunes = $totalJeunes;
                $cotisation->nombre_non_payeurs = $totalJeunes - $nombrePayeurs;
                $cotisation->total_collecte = $totalCollecte;
                $cotisation->taux_collecte = $totalJeunes > 0 ? round(($nombrePayeurs / $totalJeunes) * 100, 2) : 0;
            });

            return response()->json([
                'success' => true,
                'data' => $cotisations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cotisations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Liste des cotisations pour Jeune (actives)
    public function getCotisationsJeune(Request $request)
    {
        try {
            $cotisations = Cotisation::where('statut', 'active')
                ->orderBy('created_at', 'desc')
                ->get();

            $user = $request->user();
            $cotisations->each(function($cotisation) use ($user) {
                $cotisation->a_paye = Paiement::where('cotisation_id', $cotisation->id)
                    ->where('jeune_id', $user->id)
                    ->where('statut', 'paye')
                    ->exists();
            });

            return response()->json([
                'success' => true,
                'data' => $cotisations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cotisations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Créer une cotisation (CU)
    public function createCotisation(Request $request)
    {
        $user = $request->user();
        
        if ($user->getTable() !== 'c_u_s') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les Chefs d\'unités peuvent créer des cotisations'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'required|numeric|min:100',
            'type' => 'required|in:nationale,locale',
            'date_limite' => 'nullable|date|after:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cotisation = Cotisation::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'montant' => $request->montant,
                'type' => $request->type,
                'created_by_type' => $user->getTable(),
                'created_by_id' => $user->id,
                'date_limite' => $request->date_limite,
                'statut' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotisation créée avec succès',
                'data' => $cotisation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Modifier une cotisation
    public function updateCotisation(Request $request, $id)
    {
        $user = $request->user();
        
        $cotisation = Cotisation::find($id);
        
        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }
        
        if ($cotisation->created_by_type !== $user->getTable() || $cotisation->created_by_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette cotisation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'montant' => 'sometimes|numeric|min:100',
            'date_limite' => 'nullable|date',
            'statut' => 'sometimes|in:active,terminee,annulee'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cotisation->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cotisation modifiée avec succès',
                'data' => $cotisation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une cotisation
    public function deleteCotisation(Request $request, $id)
    {
        $user = $request->user();
        
        $cotisation = Cotisation::find($id);
        
        if (!$cotisation) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }
        
        if ($cotisation->created_by_type !== $user->getTable() || $cotisation->created_by_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette cotisation'
            ], 403);
        }

        try {
            $cotisation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotisation supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Détails d'une cotisation avec les paiements
    public function getCotisationDetails(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $cotisation = Cotisation::with(['paiements' => function($query) {
                    $query->with('jeune')->orderBy('created_at', 'desc');
                }])
                ->find($id);
            
            if (!$cotisation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotisation non trouvée'
                ], 404);
            }

            // Récupérer tous les jeunes de l'unité du CU
            $totalJeunes = Jeune::where('cu_id', $user->id)->count();
            $paiementsPayes = $cotisation->paiements->where('statut', 'paye');
            $nombrePayeurs = $paiementsPayes->count();
            $totalCollecte = $paiementsPayes->sum('montant');
            $tauxCollecte = $totalJeunes > 0 ? round(($nombrePayeurs / $totalJeunes) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'cotisation' => $cotisation,
                    'statistiques' => [
                        'montant_unitaire' => $cotisation->montant,
                        'total_jeunes' => $totalJeunes,
                        'nombre_payeurs' => $nombrePayeurs,
                        'nombre_non_payeurs' => $totalJeunes - $nombrePayeurs,
                        'total_collecte' => $totalCollecte,
                        'taux_collecte' => $tauxCollecte
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}