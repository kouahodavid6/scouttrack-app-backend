<?php
// app/Http/Controllers/PasswordResetController.php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\Nation;
use App\Models\Region;
use App\Models\District;
use App\Models\Groupe;
use App\Models\CU;
use App\Models\Jeune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Trouver l'utilisateur par email dans tous les modèles
     */
    private function findUserByEmail($email)
    {
        $models = [
            'nation' => Nation::class,
            'region' => Region::class,
            'district' => District::class,
            'groupe' => Groupe::class,
            'cu' => CU::class,
            'jeune' => Jeune::class,
        ];

        foreach ($models as $type => $model) {
            $user = $model::where('email', $email)->first();
            if ($user) {
                return ['user' => $user, 'type' => $type];
            }
        }

        return null;
    }

    /**
     * Générer un OTP à 6 chiffres
     */
    private function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Envoyer l'email avec l'OTP
     */
    private function sendOTPEmail($email, $otp, $name, $entityType = 'Utilisateur')
    {
        try {
            Mail::send('emails.password-reset', [
                'name' => $name,
                'otp' => $otp,
                'email' => $email,
                'entityType' => $entityType,
                'date' => Carbon::now()->format('d/m/Y à H:i')
            ], function ($message) use ($email) {
                $message->to($email)
                        ->subject('Réinitialisation de votre mot de passe - ScoutTrack');
            });
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Étape 1: Demander la réinitialisation (envoi OTP)
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $userInfo = $this->findUserByEmail($request->email);
            
            if (!$userInfo) {
                // Pour des raisons de sécurité, on renvoie un message générique
                return response()->json([
                    'success' => true,
                    'message' => 'Si cet email existe, vous recevrez un code de réinitialisation.'
                ], 200);
            }

            $user = $userInfo['user'];
            $entityType = ucfirst($userInfo['type']); // Nation, Region, District, Groupe, CU, Jeune
            
            // Supprimer les anciens tokens non utilisés
            PasswordReset::where('email', $request->email)
                ->where('is_used', false)
                ->delete();

            // Générer token et OTP
            $token = Str::random(60);
            $otp = $this->generateOTP();
            $expiresAt = Carbon::now()->addMinutes(15); // Valable 15 minutes

            // Créer la demande de réinitialisation
            PasswordReset::create([
                'email' => $request->email,
                'token' => $token,
                'otp_code' => $otp,
                'expires_at' => $expiresAt,
                'is_used' => false
            ]);

            // Envoyer l'email avec le type d'entité
            $emailSent = $this->sendOTPEmail(
                $request->email, 
                $otp, 
                $user->nom ?? $user->name ?? 'Utilisateur',
                $entityType
            );

            if (!$emailSent) {
                // Supprimer la demande si l'email n'a pas été envoyé
                PasswordReset::where('token', $token)->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Un code de réinitialisation a été envoyé à votre adresse email.',
                'data' => [
                    'token' => $token,
                    'expires_in' => 15
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur forgot password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Étape 2: Vérifier l'OTP
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $resetRequest = PasswordReset::where('token', $request->token)
                ->where('is_used', false)
                ->first();

            if (!$resetRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande de réinitialisation invalide ou expirée.'
                ], 400);
            }

            if (!$resetRequest->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce code a expiré. Veuillez faire une nouvelle demande.'
                ], 400);
            }

            if ($resetRequest->otp_code !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP incorrect.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Code vérifié avec succès.',
                'data' => [
                    'reset_token' => $resetRequest->token
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur verify OTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification.'
            ], 500);
        }
    }

    /**
     * Étape 3: Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $resetRequest = PasswordReset::where('token', $request->token)
                ->where('is_used', false)
                ->first();

            if (!$resetRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande de réinitialisation est invalide.'
                ], 400);
            }

            if (!$resetRequest->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande de réinitialisation a expiré. Veuillez faire une nouvelle demande.'
                ], 400);
            }

            $userInfo = $this->findUserByEmail($resetRequest->email);
            
            if (!$userInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ], 404);
            }

            $user = $userInfo['user'];
            
            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->password);
            $user->save();

            // Marquer le token comme utilisé
            $resetRequest->is_used = true;
            $resetRequest->save();

            // Supprimer tous les autres tokens pour cet email
            PasswordReset::where('email', $resetRequest->email)->delete();

            // Optionnel: Envoyer un email de confirmation de réinitialisation
            // $this->sendPasswordChangedConfirmation($resetRequest->email, $userInfo['type']);

            return response()->json([
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur reset password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la réinitialisation du mot de passe.'
            ], 500);
        }
    }

    /**
     * Renvoyer un nouvel OTP
     */
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $resetRequest = PasswordReset::where('token', $request->token)
                ->where('is_used', false)
                ->first();

            if (!$resetRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande de réinitialisation invalide.'
                ], 400);
            }

            $userInfo = $this->findUserByEmail($resetRequest->email);
            
            if (!$userInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ], 404);
            }

            $entityType = ucfirst($userInfo['type']);
            
            // Générer nouveau OTP
            $newOtp = $this->generateOTP();
            $resetRequest->otp_code = $newOtp;
            $resetRequest->expires_at = Carbon::now()->addMinutes(15);
            $resetRequest->save();

            // Envoyer le nouvel OTP avec le type d'entité
            $emailSent = $this->sendOTPEmail(
                $resetRequest->email, 
                $newOtp, 
                $userInfo['user']->nom ?? $userInfo['user']->name ?? 'Utilisateur',
                $entityType
            );

            if (!$emailSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Un nouveau code vous a été envoyé par email.',
                'data' => [
                    'expires_in' => 15
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur resend OTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du renvoi du code.'
            ], 500);
        }
    }

    /**
     * Vérifier si un token de réinitialisation est valide (optionnel)
     */
    public function checkToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $resetRequest = PasswordReset::where('token', $request->token)
                ->where('is_used', false)
                ->first();

            if (!$resetRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide'
                ], 400);
            }

            if (!$resetRequest->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expiré'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token valide',
                'data' => [
                    'email' => $resetRequest->email,
                    'expires_at' => $resetRequest->expires_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur check token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }
}