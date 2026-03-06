<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\Etape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ActiviteController extends Controller
{
    // Ajouter une activité
    public function createActivite (Request $request) {
        $validator = Validator::make($request->all(), [
            'nom_act' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'badge' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'etape_id' => 'required'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $etape = Etape::find($request->etape_id);

        if (!$etape) {
            return response()->json([
                'success' => false,
                'message' => 'Étape introuvable'
            ], 404);
        }

        // Upload du badge (seulement si présente)
        $image = null;
        if ($request->hasFile('badge') && $request->file('badge')->isValid()) {
            $image = $this->uploadImageToHosting($request->badge);
        }

        try {
            $activite = new Activite();
            $activite->nom_act = $request->nom_act;
            $activite->description = $request->description;
            $activite->date_debut = $request->date_debut;
            $activite->date_fin = $request->date_fin;
            $activite->badge = $image;
            $activite->etape_id = $request->etape_id;
            $activite->save();

            return response()->json([
                'success' => true,
                'data' => $activite,
                'message' => 'Activité créée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’activité',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Lister toutes les activités
    public function readActivites () {
        try {
            $activites = Activite::with('etape')->get();

            return response()->json([
                'success' => true,
                'data' => $activites,
                'message' => 'Liste des activités affichée avec succès'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des activités',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Modifier une activité
    public function updateActivite (Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'nom_act' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'badge' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'etape_id' => 'required'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $etape = Etape::find($request->etape_id);
        if (!$etape) {
            return response()->json([
                'success' => false,
                'message' => 'Étape introuvable'
            ], 404);
        }

        // Upload de la photo (seulement si présente)
        $image = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $image = $this->uploadImageToHosting($request->photo);
        }

        try {
            $activite = Activite::find($id);

            if (!$activite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette activité n’existe pas'
                ], 404);
            }
            $activite->nom_act = $request->nom_act ?? $activite->nom_act;
            $activite->description = $request->description ?? $activite->description;
            $activite->date_debut = $request->date_debut ?? $activite->date_debut;
            $activite->date_fin = $request->date_fin ?? $activite->date_fin;
            $activite->badge = $image ?? $activite->badge;
            $activite->etape_id = $request->etape_id ?? $activite->etape_id;
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
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une activité
    public function deleteActivite ($id) {
        try {
            $activite = Activite::find($id);

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
                'erreur' => $e->getMessage()
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
