<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\Paiement;
use App\Mail\CotisationConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    // Initier un paiement (créer une transaction Kkiapay)
    public function initierPaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cotisation_id' => 'required|exists:cotisations,id',
            'telephone' => 'required|string|regex:/^[0-9]{8,13}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cotisation = Cotisation::find($request->cotisation_id);
            $user = $request->user();

            // Vérifier si le jeune a déjà payé
            $dejaPaye = Paiement::where('cotisation_id', $cotisation->id)
                ->where('jeune_id', $user->id)
                ->where('statut', 'paye')
                ->exists();

            if ($dejaPaye) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà payé cette cotisation'
                ], 400);
            }

            // Créer un enregistrement de paiement en attente
            $paiement = Paiement::create([
                'cotisation_id' => $cotisation->id,
                'jeune_id' => $user->id,
                'jeune_nom' => $user->nom,
                'jeune_email' => $user->email,
                'montant' => $cotisation->montant,
                'numero_telephone' => $request->telephone,
                'statut' => 'en_attente',
                'transaction_id' => 'pending_' . uniqid()
            ]);

            // Configurer Kkiapay
            $kkiapayConfig = [
                'public_key' => env('KKIAPAY_PUBLIC_KEY'),
                'secret_key' => env('KKIAPAY_SECRET_KEY'),
                'sandbox' => env('KKIAPAY_SANDBOX', true)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'paiement_id' => $paiement->id,
                    'montant' => $cotisation->montant,
                    'montant_formatted' => $cotisation->montant_formatted,
                    'nom_cotisation' => $cotisation->nom,
                    'type_cotisation' => $cotisation->type,
                    'telephone' => $request->telephone,
                    'kkiapay_config' => $kkiapayConfig
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Webhook pour confirmer le paiement (appelé par Kkiapay)
    public function webhookKkiapay(Request $request)
    {
        try {
            $data = $request->all();
            
            $transactionId = $data['transaction_id'] ?? null;
            $statut = $data['status'] ?? null;
            $montant = $data['amount'] ?? null;
            $telephone = $data['phone'] ?? null;

            Log::info('Kkiapay webhook received', ['data' => $data]);

            if (!$transactionId || $statut !== 'success') {
                return response()->json(['success' => false, 'message' => 'Transaction non valide'], 400);
            }

            // Trouver le paiement correspondant
            $paiement = Paiement::where('transaction_id', 'like', "%{$transactionId}%")
                ->orWhere('transaction_id', $transactionId)
                ->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour transaction: ' . $transactionId);
                return response()->json(['success' => false, 'message' => 'Paiement non trouvé'], 404);
            }

            // Mettre à jour le paiement
            $paiement->update([
                'transaction_id' => $transactionId,
                'statut' => 'paye',
                'kkiapay_response' => $data,
                'date_paiement' => now(),
                'numero_telephone' => $telephone ?? $paiement->numero_telephone
            ]);

            // Envoyer un email de confirmation UNIQUEMENT pour les cotisations nationales
            if ($paiement->cotisation && $paiement->cotisation->type === 'nationale') {
                if ($paiement->jeune_email) {
                    try {
                        Mail::to($paiement->jeune_email)->send(new CotisationConfirmationMail($paiement));
                        Log::info('Email de confirmation envoyé à: ' . $paiement->jeune_email);
                    } catch (\Exception $e) {
                        Log::error('Erreur envoi email: ' . $e->getMessage());
                    }
                } else {
                    Log::warning('Pas d\'email pour le jeune: ' . $paiement->jeune_id);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Kkiapay webhook error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Vérifier le statut d'un paiement
    public function verifierPaiement(Request $request, $id)
    {
        try {
            $paiement = Paiement::with(['cotisation', 'jeune'])->find($id);
            
            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $paiement
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Historique des paiements d'un jeune
    public function getMesPaiements(Request $request)
    {
        try {
            $user = $request->user();
            
            $paiements = Paiement::where('jeune_id', $user->id)
                ->with('cotisation')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $paiements
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}