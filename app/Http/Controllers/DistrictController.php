<?php

namespace App\Http\Controllers;

use App\Mail\SendCredentialsMail;
use App\Models\District;
use App\Models\Region;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

// davidkouaho100@gmail.com
// DisCo5dqY6
class DistrictController extends Controller
{
    //Générer un mot de passe aléatoire
    private function generatePassword () {
        // Préfixe pour Région
        $prefix = 'Dis';
        
        // Combinaison de caractères : lettres majuscules, minuscules et chiffres
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        // Assurer au moins un caractère de chaque type
        $password = $uppercase[random_int(0, 25)];
        $password .= $lowercase[random_int(0, 25)];
        $password .= $numbers[random_int(0, 9)];

        // Ajoute 4 caractères aléatoires (car on a déjà 3 + le préfixe "Reg")
        $allChars = $uppercase . $lowercase . $numbers;
        for ($i = 0; $i < 4; $i++) { 
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Mélange le mot de passe (sans le préfixe)
        $password = str_shuffle($password);
        
        // Retourne "Reg" + mot de passe mélangé
        return $prefix . $password;
    }


    // Ajouter un district
    public function createDistrict (Request $request) {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
            'tel' => 'required|digits:10|unique:districts,tel',
            'email' => 'required|email|unique:districts,email',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Générer le mot de passe
        $generatedPassword = $this->generatePassword();
        // Upload de l'image
        $image = $this->uploadImageToHosting($request->photo);

        try {
            $user = $request->user();

            $region = Region::find($user->id);

            if (!$region) {
                return response()->json([
                    'success' => false,
                    'message' => 'Région introuvable'
                ], 404);
            }

            $district = new District();
            $district->nom = $request->nom;
            $district->niveau = $request->niveau;
            $district->tel = $request->tel;
            $district->email = $request->email;
            $district->photo = $image;
            $district->password = Hash::make($generatedPassword);
            $district->role = 1;
            $district->region_id = $region->id;
            $district->save();

            // ENVOYER L'EMAIL AVEC LE SERVICE UNIVERSEL
            Mail::to($request->email)->send(new SendCredentialsMail(
                $request->nom,
                $request->email,
                $generatedPassword,
                "compte District"  // Type d'entité
            ));

            return response()->json([
                'success' => true,
                'data' => $district,
                'message' => 'District créé avec succès. Les identifiants ont été envoyés par email.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du district',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }


    // Lister tous les districts
    public function readDistricts () {
        try {
            $districts = District::all();

            return response()->json([
                'success' => true,
                'data' => $districts,
                'message' => 'Liste des districts affichée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des districts',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }


    // Supprimer un district
    public function deleteDistrict ($id) {
        try {
            $district = District::find($id);

            if (!$district) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le district que vous voulez supprimer n’existe pas'
                ], 404);
            }
            $district->delete();

            return response()->json([
                'success' => true,
                'message' => 'District supprimé avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du district',
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
