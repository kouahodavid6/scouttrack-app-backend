<!-- resources/views/emails/password-reset.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Réinitialisation de mot de passe - ScoutTrack</title>
    <style>
        /* Reset CSS pour compatibilité email */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, table, td, div, p, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            width: 100% !important;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Container principal */
        .email-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Header avec thème violet */
        .email-header {
            background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
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
            position: relative;
            z-index: 1;
        }
        
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .header-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }
        
        /* Contenu */
        .email-content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 20px;
            margin-bottom: 25px;
            color: #1F2937;
            font-weight: 600;
        }
        
        .greeting strong {
            color: #7C3AED;
        }
        
        .message {
            font-size: 16px;
            margin-bottom: 30px;
            color: #4B5563;
            line-height: 1.7;
        }
        
        /* Box OTP */
        .otp-box {
            background: linear-gradient(135deg, #F3F4F6 0%, #FFFFFF 100%);
            border: 2px solid #E5E7EB;
            border-left: 6px solid #7C3AED;
            padding: 30px;
            margin: 30px 0;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .otp-title {
            font-size: 18px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .otp-title:before {
            content: "🔐";
            font-size: 22px;
        }
        
        .otp-code {
            background: white;
            padding: 25px;
            text-align: center;
            font-size: 48px;
            letter-spacing: 12px;
            font-weight: bold;
            color: #7C3AED;
            border-radius: 12px;
            margin: 20px 0;
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            border: 2px solid #E5E7EB;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .otp-expiry {
            display: inline-block;
            background: #FEF3C7;
            color: #92400E;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 15px;
        }
        
        /* Box d'information */
        .info-box {
            background: linear-gradient(135deg, #EFF6FF 0%, #FFFFFF 100%);
            border: 2px solid #BFDBFE;
            border-left: 6px solid #3B82F6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 10px;
        }
        
        .info-title {
            font-size: 16px;
            font-weight: 700;
            color: #1E40AF;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-title:before {
            content: "ℹ️";
            font-size: 18px;
        }
        
        .info-text {
            font-size: 14px;
            color: #1E3A8A;
            line-height: 1.6;
        }
        
        /* Bouton de réinitialisation */
        .reset-button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 17px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
        }
        
        /* Warning box */
        .warning-box {
            background: linear-gradient(to right, #FEF3C7, #FFFBEB);
            border: 2px solid #FBBF24;
            color: #92400E;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .warning-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .warning-text {
            font-size: 15px;
            line-height: 1.6;
        }
        
        .warning-text strong {
            color: #92400E;
        }
        
        /* Steps */
        .steps-container {
            background-color: #F9FAFB;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .steps-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .steps-title:before {
            content: "📱";
            font-size: 22px;
        }
        
        .steps-list {
            padding-left: 0;
            margin: 0;
            list-style: none;
        }
        
        .steps-list li {
            margin: 15px 0;
            padding-left: 30px;
            position: relative;
            color: #4B5563;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .steps-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #7C3AED;
            font-weight: bold;
            font-size: 18px;
        }
        
        .steps-list strong {
            color: #7C3AED;
        }
        
        /* Signature */
        .signature {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
        }
        
        .signature-text {
            font-size: 16px;
            color: #4B5563;
            margin: 8px 0;
        }
        
        .team-name {
            color: #7C3AED;
            font-weight: 700;
            font-size: 18px;
        }
        
        /* Footer */
        .email-footer {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: #D1D5DB;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer-logo-text {
            font-size: 28px;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        
        .footer-text {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .footer-link {
            color: #8B5CF6;
            text-decoration: none;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        .copyright {
            margin-top: 20px;
            color: #9CA3AF;
            font-size: 12px;
            border-top: 1px solid #374151;
            padding-top: 15px;
        }
        
        /* Badge type d'entité */
        .entity-badge {
            display: inline-block;
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
            box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);
        }
        
        /* Responsive */
        @media only screen and (max-width: 620px) {
            body {
                padding: 10px;
            }
            
            .email-wrapper {
                border-radius: 0;
                box-shadow: none;
            }
            
            .email-content {
                padding: 25px 20px;
            }
            
            .email-header {
                padding: 25px 15px;
            }
            
            .header-title {
                font-size: 22px;
            }
            
            .logo-text {
                font-size: 28px;
            }
            
            .otp-box {
                padding: 20px;
            }
            
            .otp-code {
                font-size: 32px;
                letter-spacing: 8px;
                padding: 20px;
            }
            
            .reset-button {
                padding: 14px 30px;
                font-size: 16px;
                width: 100%;
            }
            
            .steps-container {
                padding: 20px;
            }
            
            .warning-box {
                flex-direction: column;
                text-align: center;
            }
            
            .warning-icon {
                margin: 0 auto;
            }
        }
        
        @media only screen and (max-width: 480px) {
            .email-header {
                padding: 20px 15px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .logo-text {
                font-size: 24px;
            }
            
            .greeting {
                font-size: 18px;
            }
            
            .message {
                font-size: 15px;
            }
            
            .otp-code {
                font-size: 28px;
                letter-spacing: 6px;
            }
            
            .otp-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <div class="header-overlay"></div>
            <div class="logo-container">
                <h1 class="logo-text">🏕️ ScoutTrack</h1>
            </div>
            <h2 class="header-title">Réinitialisation du mot de passe</h2>
            <p class="header-subtitle">Sécurisez votre accès à la plateforme</p>
        </div>
        
        <!-- Contenu principal -->
        <div class="email-content">
            <!-- Salutation -->
            <p class="greeting">
                Bonjour <strong>{{ $name }}</strong>
                @if(isset($entityType))
                <span class="entity-badge">{{ $entityType }}</span>
                @endif
            </p>
            
            <!-- Message d'introduction -->
            <p class="message">
                Vous avez demandé la réinitialisation de votre mot de passe pour votre compte ScoutTrack 
                associé à l'adresse email : <strong>{{ $email }}</strong>.
            </p>
            
            <p class="message">
                Pour des raisons de sécurité, veuillez utiliser le code ci-dessous pour réinitialiser votre mot de passe.
                Ce code est valable pendant <strong>15 minutes</strong>.
            </p>
            
            <!-- Box OTP -->
            <div class="otp-box">
                <h3 class="otp-title">Code de vérification</h3>
                
                <div class="otp-code">
                    {{ $otp }}
                </div>
                
                <div class="otp-expiry">
                    ⏱️ Expire dans 15 minutes
                </div>
            </div>
            
            <!-- Box d'information -->
            <div class="info-box">
                <h4 class="info-title">Comment utiliser ce code ?</h4>
                <div class="info-text">
                    <ol style="margin: 10px 0 0 20px; padding: 0;">
                        <li style="margin: 8px 0;">Saisissez ce code à 6 chiffres dans l'application</li>
                        <li style="margin: 8px 0;">Choisissez un nouveau mot de passe sécurisé</li>
                        <li style="margin: 8px 0;">Connectez-vous avec votre nouveau mot de passe</li>
                    </ol>
                </div>
            </div>
            
            <!-- Bouton d'accès (optionnel, si tu veux rediriger vers l'app) -->
            <div class="reset-button-container">
                <a href="https://scouttrack-app.vercel.app/reset-password" class="reset-button">
                    🔄 Réinitialiser mon mot de passe
                </a>
                <p style="margin-top: 12px; color: #6B7280; font-size: 14px;">
                    <em>Cliquez sur ce bouton pour continuer le processus de réinitialisation</em>
                </p>
            </div>
            
            <!-- Warning important -->
            <div class="warning-box">
                <div class="warning-icon">⚠️</div>
                <div class="warning-text">
                    <strong>Important :</strong> Si vous n'avez pas demandé cette réinitialisation, 
                    veuillez ignorer cet email. Votre mot de passe restera inchangé.<br><br>
                    <strong>Ne partagez jamais ce code avec qui que ce soit.</strong>
                </div>
            </div>
            
            <!-- Guide de sécurité -->
            <div class="steps-container">
                <h3 class="steps-title">Conseils de sécurité</h3>
                <ul class="steps-list">
                    <li>Utilisez un mot de passe unique, différent de vos autres comptes</li>
                    <li>Choisissez un mot de passe d'au moins 8 caractères avec lettres, chiffres et symboles</li>
                    <li>Activez la double authentification si disponible</li>
                    <li>Déconnectez-vous toujours après utilisation sur un appareil public</li>
                    <li>En cas de doute, contactez immédiatement notre support</li>
                </ul>
            </div>
            
            <!-- Assistance -->
            <div style="background-color: #E0E7FF; border-radius: 10px; padding: 20px; margin: 30px 0; text-align: center;">
                <p style="margin: 0; color: #3730A3; font-size: 15px;">
                    <strong>❓ Besoin d'aide ?</strong> Contactez notre support à 
                    <a href="mailto:support@scouttrack.com" style="color: #7C3AED; font-weight: 600;">support@scouttrack.com</a>
                </p>
            </div>
            
            <!-- Signature -->
            <div class="signature">
                <p class="signature-text">L'équipe ScoutTrack reste à votre disposition pour toute assistance.</p>
                <p class="team-name">L'équipe ScoutTrack</p>
                <p class="signature-text" style="margin-top: 15px; font-size: 14px;">
                    Ensemble pour un meilleur suivi scout
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-logo-text">🏕️ ScoutTrack</div>
            
            <p class="footer-text">
                ScoutTrack - Plateforme de gestion scoute intelligente
            </p>
            <p class="footer-text">
                <a href="https://scouttrack.vercel.app/" class="footer-link">Visitez notre site web</a> | 
                <a href="https://scouttrack-app.vercel.app" class="footer-link">Accéder à l'application</a>
            </p>
            <p class="footer-text">
                📞 Support : <a href="tel:+2250171136261" class="footer-link">+225 01 71 13 62 61</a><br>
                📧 Email : <a href="mailto:contact@scouttrack.com" class="footer-link">contact@scouttrack.com</a>
            </p>
            
            <div class="copyright">
                © {{ date('Y') }} ScoutTrack Platform. Tous droits réservés.<br>
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.<br>
                Code de sécurité généré le {{ date('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html>