<?php
// app/Http/Controllers/AnnonceController.php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Nation;
use App\Models\Region;
use App\Models\District;
use App\Models\Groupe;
use App\Models\CU;
use App\Models\Jeune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnnonceController extends Controller
{
    /**
     * Récupérer les annonces pour l'utilisateur connecté (lecture)
     */
    public function readAnnonces()
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);
            $userId = $user->id;

            $annonces = Annonce::where('is_published', true)
                ->where(function ($query) use ($userType, $userId) {
                    // Annonces ciblant directement l'utilisateur
                    $query->where('target_type', $userType)
                        ->where(function ($q) use ($userId) {
                            $q->whereNull('target_ids')
                                ->orWhereJsonContains('target_ids', $userId);
                        });

                    // Annonces des supérieurs (lecture seule)
                    $superieurs = $this->getSuperieurs($userType);
                    foreach ($superieurs as $superieur) {
                        $query->orWhere('target_type', $superieur)
                            ->whereNull('target_ids');
                    }
                })
                ->where(function ($query) {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', Carbon::now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', Carbon::now());
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $annonces,
                'message' => 'Liste des annonces affichée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des annonces',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les annonces créées par l'utilisateur (pour CRUD)
     */
    public function readMesAnnonces()
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);

            $annonces = Annonce::where('created_by_type', $userType)
                ->where('created_by_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $annonces,
                'message' => 'Vos annonces affichées avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de vos annonces',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les cibles possibles pour l'utilisateur
     */
    // app/Http/Controllers/AnnonceController.php
    public function getCibles()
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);
            $cibles = [
                'type' => null,
                'items' => []
            ];

            switch ($userType) {
                case 'nation':
                    $nation = Nation::find($user->id);
                    $cibles = [
                        'type' => 'region',
                        'items' => $nation ? $nation->regions()->select('id', 'nom')->get() : []
                    ];
                    break;
                case 'region':
                    $region = Region::find($user->id);
                    $cibles = [
                        'type' => 'district',
                        'items' => $region ? $region->districts()->select('id', 'nom')->get() : []
                    ];
                    break;
                case 'district':
                    $district = District::find($user->id);
                    $cibles = [
                        'type' => 'groupe',
                        'items' => $district ? $district->groupes()->select('id', 'nom')->get() : []
                    ];
                    break;
                case 'groupe':
                    $groupe = Groupe::find($user->id);
                    $cibles = [
                        'type' => 'cu',
                        'items' => $groupe ? $groupe->cus()->select('id', 'nom')->get() : []
                    ];
                    break;
                case 'cu':
                    $cu = CU::find($user->id);
                    $cibles = [
                        'type' => 'jeune',
                        'items' => $cu ? $cu->jeunes()->select('id', 'nom')->get() : []  // ← Correction ici
                    ];
                    break;
                default:
                    $cibles = ['type' => null, 'items' => []];
            }

            return response()->json([
                'success' => true,
                'data' => $cibles,
                'message' => 'Liste des cibles affichée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des cibles',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une annonce
     */
    public function createAnnonce(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'type' => 'required|in:annonce,actualite',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'string',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();
        $userType = $this->getUserType($user);
        $targetType = $this->getTargetType($userType);

        if (!$targetType) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de créer des annonces'
            ], 403);
        }

        try {
            $annonce = new Annonce();
            $annonce->titre = $request->titre;
            $annonce->contenu = $request->contenu;
            $annonce->type = $request->type;
            $annonce->created_by_id = $user->id;
            $annonce->created_by_type = $userType;
            $annonce->target_type = $targetType;
            $annonce->target_ids = $request->target_ids;
            $annonce->is_published = true;
            $annonce->published_at = $request->published_at ? Carbon::parse($request->published_at) : Carbon::now();
            $annonce->expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : null;
            $annonce->save();

            return response()->json([
                'success' => true,
                'data' => $annonce,
                'message' => 'Annonce créée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'annonce',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifier une annonce
     */
    public function updateAnnonce(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|string|max:255',
            'contenu' => 'sometimes|string',
            'type' => 'sometimes|in:annonce,actualite',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'string',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();
        $userType = $this->getUserType($user);

        try {
            $annonce = Annonce::where('id', $id)
                ->where('created_by_type', $userType)
                ->where('created_by_id', $user->id)
                ->first();

            if (!$annonce) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette annonce n\'existe pas ou vous n\'avez pas le droit de la modifier'
                ], 404);
            }

            if ($request->has('titre')) {
                $annonce->titre = $request->titre;
            }
            if ($request->has('contenu')) {
                $annonce->contenu = $request->contenu;
            }
            if ($request->has('type')) {
                $annonce->type = $request->type;
            }
            if ($request->has('target_ids')) {
                $annonce->target_ids = $request->target_ids;
            }
            if ($request->has('published_at')) {
                $annonce->published_at = $request->published_at ? Carbon::parse($request->published_at) : Carbon::now();
            }
            if ($request->has('expires_at')) {
                $annonce->expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : null;
            }
            $annonce->save();

            return response()->json([
                'success' => true,
                'data' => $annonce,
                'message' => 'Annonce modifiée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'annonce',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une annonce
     */
    public function deleteAnnonce($id)
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);

            $annonce = Annonce::where('id', $id)
                ->where('created_by_type', $userType)
                ->where('created_by_id', $user->id)
                ->first();

            if (!$annonce) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette annonce n\'existe pas ou vous n\'avez pas le droit de la supprimer'
                ], 404);
            }

            $annonce->delete();

            return response()->json([
                'success' => true,
                'message' => 'Annonce supprimée avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'annonce',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: obtenir le type de l'utilisateur
     */
    private function getUserType($user)
    {
        if ($user instanceof \App\Models\Nation) return 'nation';
        if ($user instanceof \App\Models\Region) return 'region';
        if ($user instanceof \App\Models\District) return 'district';
        if ($user instanceof \App\Models\Groupe) return 'groupe';
        if ($user instanceof \App\Models\CU) return 'cu';
        if ($user instanceof \App\Models\Jeune) return 'jeune';
        return null;
    }

    /**
     * Helper: obtenir le type cible selon le créateur
     */
    private function getTargetType($userType)
    {
        return match($userType) {
            'nation' => 'region',
            'region' => 'district',
            'district' => 'groupe',
            'groupe' => 'cu',
            'cu' => 'jeune',
            default => null
        };
    }

    /**
     * Helper: obtenir les types supérieurs pour la lecture
     */
    private function getSuperieurs($userType)
    {
        return match($userType) {
            'jeune' => ['cu', 'groupe', 'district', 'region', 'nation'],
            'cu' => ['groupe', 'district', 'region', 'nation'],
            'groupe' => ['district', 'region', 'nation'],
            'district' => ['region', 'nation'],
            'region' => ['nation'],
            default => []
        };
    }
}