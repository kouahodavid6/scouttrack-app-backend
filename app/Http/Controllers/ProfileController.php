<?php

namespace App\Http\Controllers;

use App\Models\Nation;
use App\Models\Region;
use App\Models\District;
use App\Models\Groupe;
use App\Models\CU;
use App\Models\Jeune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Récupérer le profil de l'utilisateur connecté
     * GET /api/profile
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'nom' => $user->nom ?? '',
                    'email' => $user->email ?? '',
                    'telephone' => $user->tel ?? '',
                    'photo' => $user->photo ?? null,
                ],
                'message' => 'Profil récupéré avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur getProfile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil'
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil
     * POST /api/profile/update
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Règles de validation simplifiées
            $rules = [
                'nom' => 'required|string|min:2|max:100',
                'email' => 'required|email|max:255|unique:' . $user->getTable() . ',email,' . $user->id,
                'telephone' => 'nullable|string|min:8|max:20|regex:/^[0-9+\s-]+$/',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'remove_photo' => 'nullable|in:true,false,1,0'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Gestion de la photo
            if ($request->hasFile('photo')) {
                $photoUrl = $this->uploadImageToHosting($request->file('photo'));
                if ($photoUrl) {
                    $user->photo = $photoUrl;
                }
            } elseif ($request->has('remove_photo') && in_array($request->remove_photo, ['true', '1', 1, true])) {
                $user->photo = null;
            }

            // Mise à jour des autres champs
            $user->nom = $request->nom;
            $user->email = $request->email;
            $user->tel = $request->telephone;
            
            $user->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'telephone' => $user->tel,
                    'photo' => $user->photo,
                ],
                'message' => 'Profil mis à jour avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur updateProfile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     * POST /api/profile/change-password
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'ancien_password' => 'required|string',
                'nouveau_password' => 'required|string|min:8|confirmed',
                'nouveau_password_confirmation' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier l'ancien mot de passe
            if (!Hash::check($request->ancien_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 401);
            }

            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->nouveau_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur changePassword: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe'
            ], 500);
        }
    }

    /**
     * Upload d'image vers ImgBB
     */
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