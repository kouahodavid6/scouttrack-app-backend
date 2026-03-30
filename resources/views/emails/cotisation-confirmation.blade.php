<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de paiement - ScoutTrack</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .logo {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        .header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0icGF0dGVybiIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVHJhbnNmb3JtPSJyb3RhdGUoNDUpIj48cGF0aCBkPSJNIDAgMCBMIDAgNDAgTCA0MCA0MCBMIDQwIDAgWiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwgMjU1LCAyNTUsIDAuMSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuKSIvPjwvc3ZnPg==');
            opacity: 0.1;
        }
        .logo-container {
            margin-bottom: 15px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .content {
            padding: 30px;
        }
        .receipt {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #7c3aed;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .receipt-label {
            font-weight: bold;
            color: #555;
        }
        .receipt-value {
            color: #333;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #7c3aed;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #e5e5e5;
        }
        .badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-overlay"></div>
            <div class="logo-container">
                <h1 class="logo-text">🏕️ ScoutTrack</h1>
            </div>
            <p style="margin: 5px 0 0; opacity: 0.9;">Gestion moderne du scoutisme</p>
        </div>
        
        <div class="content">
            <div style="text-align: center; margin-bottom: 20px;">
                <span class="badge">Paiement confirmé ✓</span>
            </div>
            
            <h2 style="color: #333; margin-top: 0;">Bonjour {{ $paiement->jeune_nom }},</h2>
            <p>Nous vous confirmons la réception de votre paiement pour la cotisation <strong>{{ $cotisation->nom }}</strong>.</p>
            
            <div class="receipt">
                <h3 style="margin: 0 0 15px 0; color: #7c3aed;">📄 Reçu de paiement</h3>
                
                <div class="receipt-row">
                    <span class="receipt-label">Référence :</span>
                    <span class="receipt-value">{{ $paiement->reference }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Date :</span>
                    <span class="receipt-value">{{ $paiement->date_paiement->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payeur :</span>
                    <span class="receipt-value">{{ $paiement->jeune_nom }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Téléphone :</span>
                    <span class="receipt-value">{{ $paiement->numero_telephone }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Cotisation :</span>
                    <span class="receipt-value">{{ $cotisation->nom }}</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Type :</span>
                    <span class="receipt-value">{{ $cotisation->type === 'nationale' ? 'Cotisation Nationale' : 'Cotisation Locale' }}</span>
                </div>
                @if($cotisation->description)
                <div class="receipt-row">
                    <span class="receipt-label">Description :</span>
                    <span class="receipt-value">{{ $cotisation->description }}</span>
                </div>
                @endif
                <div class="receipt-row">
                    <span class="receipt-label">Transaction :</span>
                    <span class="receipt-value">{{ $paiement->transaction_id }}</span>
                </div>
                <div class="total">
                    Montant total : {{ $paiement->montant_formatted }}
                </div>
            </div>
            
            <p style="margin-top: 20px;">Merci pour votre contribution au mouvement scout !</p>
            <p>Cordialement,<br><strong>L'équipe ScoutTrack</strong></p>
        </div>
        
        <div class="footer">
            <p>ScoutTrack - Plateforme de gestion du mouvement scout</p>
            <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
            <p>© {{ date('Y') }} ScoutTrack. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>