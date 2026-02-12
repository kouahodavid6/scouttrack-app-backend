<?php

namespace App\Http\Controllers;

use App\Models\Nation;
use App\Models\Region;
use App\Models\District;
use App\Models\Groupe;
use App\Models\CU;
use App\Models\Jeune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        // Erreur de validation
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 1. Vérifier Nation
            $nation = Nation::where('email', $request->email)->first();
            if ($nation && Hash::check($request->password, $nation->password)) {
                $token = $nation->createToken('nation_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $nation->id,
                        'nom' => $nation->nom,
                        'niveau' => $nation->niveau,
                        'tel' => $nation->tel,
                        'email' => $nation->email,
                        'photo' => $nation->photo,
                        'role' => $nation->role,
                        'type' => 'nation',
                        'token' => $token
                    ],
                    'message' => 'Nation connectée avec succès.'
                ], 200);
            }

            // 2. Vérifier Region
            $region = Region::where('email', $request->email)->first();
            if ($region && Hash::check($request->password, $region->password)) {
                $token = $region->createToken('region_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $region->id,
                        'nom' => $region->nom,
                        'niveau' => $region->niveau,
                        'tel' => $region->tel,
                        'email' => $region->email,
                        'photo' => $region->photo,
                        'nation_id' => $region->nation_id,
                        'role' => $region->role,
                        'type' => 'region',
                        'token' => $token
                    ],
                    'message' => 'Région connectée avec succès.'
                ], 200);
            }

            // 3. Vérifier District
            $district = District::where('email', $request->email)->first();
            if ($district && Hash::check($request->password, $district->password)) {
                $token = $district->createToken('district_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $district->id,
                        'nom' => $district->nom,
                        'niveau' => $district->niveau,
                        'tel' => $district->tel,
                        'email' => $district->email,
                        'photo' => $district->photo,
                        'region_id' => $district->region_id,
                        'role' => $district->role,
                        'type' => 'district',
                        'token' => $token
                    ],
                    'message' => 'District connecté avec succès.'
                ], 200);
            }

            // 4. Vérifier Groupe
            $groupe = Groupe::where('email', $request->email)->first();
            if ($groupe && Hash::check($request->password, $groupe->password)) {
                $token = $groupe->createToken('groupe_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $groupe->id,
                        'nom' => $groupe->nom,
                        'niveau' => $groupe->niveau,
                        'tel' => $groupe->tel,
                        'email' => $groupe->email,
                        'photo' => $groupe->photo,
                        'district_id' => $groupe->district_id,
                        'role' => $groupe->role,
                        'type' => 'groupe',
                        'token' => $token
                    ],
                    'message' => 'Groupe connecté avec succès.'
                ], 200);
            }

            // 5. Vérifier CU
            $cu = CU::where('email', $request->email)->first();
            if ($cu && Hash::check($request->password, $cu->password)) {
                $token = $cu->createToken('cu_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $cu->id,
                        'nom' => $cu->nom,
                        'niveau' => $cu->niveau,
                        'tel' => $cu->tel,
                        'email' => $cu->email,
                        'photo' => $cu->photo,
                        'groupe_id' => $cu->groupe_id,
                        'role' => $cu->role,
                        'type' => 'cu',
                        'token' => $token
                    ],
                    'message' => 'CU connecté avec succès.'
                ], 200);
            }

            // 6. Vérifier Jeune
            $jeune = Jeune::where('email', $request->email)->first();
            if ($jeune && Hash::check($request->password, $jeune->password)) {
                $token = $jeune->createToken('jeune_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $jeune->id,
                        'nom' => $jeune->nom,
                        'age' => $jeune->age,
                        'niveau' => $jeune->niveau,
                        'tel' => $jeune->tel,
                        'email' => $jeune->email,
                        'photo' => $jeune->photo,
                        'cu_id' => $jeune->cu_id,
                        'role' => $jeune->role,
                        'type' => 'jeune',
                        'token' => $token
                    ],
                    'message' => 'Jeune connecté avec succès.'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrects'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    // Vérifier l'utilisateur connecté
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            // Déterminer le type d'utilisateur
            $type = '';
            $modelClass = get_class($user);
            
            if ($modelClass === 'App\Models\Nation') $type = 'nation';
            elseif ($modelClass === 'App\Models\Region') $type = 'region';
            elseif ($modelClass === 'App\Models\District') $type = 'district';
            elseif ($modelClass === 'App\Models\Groupe') $type = 'groupe';
            elseif ($modelClass === 'App\Models\CU') $type = 'cu';
            elseif ($modelClass === 'App\Models\Jeune') $type = 'jeune';
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'type' => $type
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations'
            ], 500);
        }
    }
}