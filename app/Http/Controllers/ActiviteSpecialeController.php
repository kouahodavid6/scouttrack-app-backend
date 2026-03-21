<?php

namespace App\Http\Controllers;

use App\Models\ActiviteSpeciale;
use App\Models\CU;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ActiviteSpecialeController extends Controller
{
    /**
     * Récupérer les types d'activités
     */
    public function getTypes()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['value' => 'sortie', 'label' => 'Sortie', 'icon' => 'Mountain'],
                ['value' => 'camp', 'label' => 'Camp', 'icon' => 'Tent'],
                ['value' => 'rencontre', 'label' => 'Rencontre', 'icon' => 'Users'],
                ['value' => 'service', 'label' => 'Service communautaire', 'icon' => 'Heart'],
                ['value' => 'formation', 'label' => 'Formation', 'icon' => 'GraduationCap'],
                ['value' => 'celebrations', 'label' => 'Célébration', 'icon' => 'PartyPopper'],
                ['value' => 'sport', 'label' => 'Sport', 'icon' => 'Activity'],
                ['value' => 'autre', 'label' => 'Autre', 'icon' => 'Star'],
            ]
        ]);
    }

    /**
     * Ajouter une activité spéciale
     */
    public function createActiviteSpeciale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:sortie,camp,rencontre,service,formation,celebrations,sport,autre',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'lieu' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Upload de l'image
        $image = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $this->uploadImageToHosting($request->image);
        }

        try {
            $cu = $request->user();

            if (!$cu || $cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            $activite = new ActiviteSpeciale();
            $activite->nom = $request->nom;
            $activite->description = $request->description;
            $activite->type = $request->type;
            $activite->date_debut = $request->date_debut;
            $activite->date_fin = $request->date_fin;
            $activite->heure_debut = $request->heure_debut;
            $activite->heure_fin = $request->heure_fin;
            $activite->lieu = $request->lieu;
            $activite->image = $image;
            $activite->cu_id = $cu->id;
            $activite->save();

            return response()->json([
                'success' => true,
                'data' => $activite,
                'message' => 'Activité créée avec succès'
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister toutes les activités spéciales du CU connecté
     */
    public function readActivitesSpeciales(Request $request)
    {
        try {
            $cu = $request->user();

            if (!$cu || $cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            $activites = ActiviteSpeciale::where('cu_id', $cu->id)
                ->orderBy('date_debut', 'desc')
                ->get()
                ->map(function ($activite) {
                    return [
                        'id' => $activite->id,
                        'nom' => $activite->nom,
                        'description' => $activite->description,
                        'type' => $activite->type,
                        'date_debut' => $activite->date_debut,
                        'date_fin' => $activite->date_fin,
                        'heure_debut' => $activite->heure_debut,
                        'heure_fin' => $activite->heure_fin,
                        'lieu' => $activite->lieu,
                        'image' => $activite->image,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activites,
                'message' => 'Liste des activités affichée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des activités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une activité par ID
     */
    public function getActiviteById(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu || $cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            $activite = ActiviteSpeciale::where('cu_id', $cu->id)->find($id);

            if (!$activite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activité non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $activite,
                'message' => 'Activité récupérée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l’activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifier une activité spéciale
     */
    public function updateActiviteSpeciale(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:sortie,camp,rencontre,service,formation,celebrations,sport,autre',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'lieu' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_image' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $cu = $request->user();

            if (!$cu || $cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            $activite = ActiviteSpeciale::where('cu_id', $cu->id)->find($id);

            if (!$activite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette activité n’existe pas'
                ], 404);
            }

            // Gestion de l'image
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $this->uploadImageToHosting($request->image);
                $activite->image = $image;
            } elseif ($request->remove_image) {
                $activite->image = null;
            }

            // Mise à jour des champs
            $activite->nom = $request->nom;
            $activite->description = $request->description;
            $activite->type = $request->type;
            $activite->date_debut = $request->date_debut;
            $activite->date_fin = $request->date_fin;
            $activite->heure_debut = $request->heure_debut;
            $activite->heure_fin = $request->heure_fin;
            $activite->lieu = $request->lieu;
            $activite->save();

            return response()->json([
                'success' => true,
                'data' => $activite,
                'message' => 'Activité modifiée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l’activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une activité spéciale
     * ✅ CORRIGÉ: Le nom de la méthode doit correspondre à la route
     */
    public function deleteActiviteSpeciale(Request $request, $id)
    {
        try {
            $cu = $request->user();

            if (!$cu || $cu->role !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            $activite = ActiviteSpeciale::where('cu_id', $cu->id)->find($id);

            if (!$activite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette activité n’existe pas'
                ], 404);
            }

            $activite->delete();

            return response()->json([
                'success' => true,
                'message' => 'Activité supprimée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l’activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les activités pour un jeune (lecture seule)
     */
    public function getForJeune(Request $request)
    {
        try {
            $jeune = $request->user();

            if (!$jeune || !$jeune->cu_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Vous n\'êtes pas assigné à un chef d\'unité'
                ], 200);
            }

            $activites = ActiviteSpeciale::where('cu_id', $jeune->cu_id)
                ->orderBy('date_debut', 'desc')
                ->get()
                ->map(function ($activite) {
                    return [
                        'id' => $activite->id,
                        'nom' => $activite->nom,
                        'description' => $activite->description,
                        'type' => $activite->type,
                        'date_debut' => $activite->date_debut,
                        'date_fin' => $activite->date_fin,
                        'heure_debut' => $activite->heure_debut,
                        'heure_fin' => $activite->heure_fin,
                        'lieu' => $activite->lieu,
                        'image' => $activite->image,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activites,
                'message' => 'Activités récupérées avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des activités'
            ], 500);
        }
    }

    // Fonction pour uploader l'image vers le service d'hébergement gratuit (ImgBB)
    private function uploadImageToHosting($image) {
        $apiKey = 'd81ec57fb36de1981c2ae96a7a4c47f6';

        if (!$image->isValid()) {
            throw new \Exception("Fichier image non valide.");
        }

        $imageContent = base64_encode(file_get_contents($image->getRealPath()));

        // SOLUTION au problème SSL : désactiver la vérification pour le développement
        // Note: En production, configurez plutôt les certificats SSL correctement
        
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withoutVerifying() // Désactive la vérification SSL
            ->timeout(30) // Timeout de 30 secondes
            ->asForm()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => $apiKey,
                'image' => $imageContent,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['data']['url'])) {
                return $data['data']['url'];
            } else {
                throw new \Exception("URL d'image non reçue de l'API ImgBB");
            }
        }

        // Journaliser les erreurs pour débogage
        $status = $response->status();
        $body = $response->body();
        
        throw new \Exception("Erreur ImgBB (Status {$status}): " . $body);
    }
}
