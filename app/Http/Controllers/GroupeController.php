<?php

namespace App\Http\Controllers;

use App\Mail\SendCredentialsMail;
use App\Models\District;
use App\Models\Groupe;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

// kouahodavid007@gmail.com
// GroA6Sct0X
class GroupeController extends Controller
{
    //Générer un mot de passe aléatoire
    private function generatePassword () {
        // Préfixe pour Région
        $prefix = 'Gro';
        
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


    // Ajouter un groupe
    public function createGroupe (Request $request) {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
            'tel' => 'required|digits:10|unique:groupes,tel',
            'email' => 'required|email|unique:groupes,email',
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
        // Upload de la photo (seulement si présente)
        $image = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $image = $this->uploadImageToHosting($request->photo);
        }

        try {
            $user = $request->user();

            $district = District::find($user->id);

            if (!$district) {
                return response()->json([
                    'success' => false,
                    'message' => 'District introuvable'
                ], 404);
            }

            $groupe = new Groupe();
            $groupe->nom = $request->nom;
            $groupe->niveau = $request->niveau;
            $groupe->tel = $request->tel;
            $groupe->email = $request->email;
            $groupe->photo = $image;
            $groupe->password = Hash::make($generatedPassword);
            $groupe->role = 1;
            $groupe->district_id = $district->id;
            $groupe->save();

            // ENVOYER L'EMAIL AVEC LE SERVICE UNIVERSEL
            Mail::to($request->email)->send(new SendCredentialsMail(
                $request->nom,
                $request->email,
                $generatedPassword,
                "compte Groupe"  // Type d'entité
            ));

            return response()->json([
                'success' => true,
                'data' => $groupe,
                'message' => 'Groupe créé avec succès. Les identifiants ont été envoyés par email.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du groupe',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }


    // Lister tous les groupes
    public function readGroupes () {
        try {
            $groupes = Groupe::all();

            return response()->json([
                'success' => true,
                'data' => $groupes,
                'message' => 'Liste des groupes affichée avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des groupes',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer un groupe
    public function deleteGroupe ($id) {
        try {
            $groupe = Groupe::find($id);

            if (!$groupe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le groupe que vous voulez supprimer n’existe pas'
                ], 404);
            }
            $groupe->delete();

            return response()->json([
                'success' => true,
                'message' => 'Groupe supprimé avec succès'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du groupe',
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