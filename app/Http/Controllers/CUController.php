<?php

namespace App\Http\Controllers;

use App\Mail\SendCredentialsMail;
use App\Models\CU;
use App\Models\Groupe;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

// jokereasy10@gmail.com
// C-UFBbW6ef
class CUController extends Controller
{
    //Générer un mot de passe aléatoire
    private function generatePassword () {
        // Préfixe pour Région
        $prefix = 'C-U';
        
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


    // Ajouter un chef d'unité
    public function createCU (Request $request) {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
            'tel' => 'required|digits:10|unique:c_u_s,tel',
            'email' => 'required|email|unique:c_u_s,email',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
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

            $groupe = Groupe::find($user->id);

            if (!$groupe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Groupe introuvable'
                ], 404);
            }

            $cu = new CU();
            $cu->nom = $request->nom;
            $cu->niveau = $request->niveau;
            $cu->tel = $request->tel;
            $cu->email = $request->email;
            $cu->photo = $image;
            $cu->password = Hash::make($generatedPassword);
            $cu->role = 1;
            $cu->groupe_id = $groupe->id;
            $cu->save();

            // ENVOYER L'EMAIL AVEC LE SERVICE UNIVERSEL
            Mail::to($request->email)->send(new SendCredentialsMail(
                $request->nom,
                $request->email,
                $generatedPassword,
                "compte Chef d'unité"  // Type d'entité
            ));

            return response()->json([
                'success' => true,
                'data' => $cu,
                'message' => 'Chef d’unité créé avec succès. Les identifiants ont été envoyés par email.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du chef d’unité',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }


    // Lister tous les chefs d'unités
    public function readCUs () {
        try {
            $cus = CU::all();

            return response()->json([
                'success' => true,
                'data' => $cus,
                'message' => 'Liste des Chefs d’unités affichée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des chefs d’unités',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer un chef d'unité
    public function deleteCU ($id) {
        try {
            $cu = CU::find($id);

            if (!$cu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le chef d’unité que vous voulez supprimer n’existe pas'
                ], 404);
            }
            $cu->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chef d’unité supprimé avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du chef d’unité',
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
