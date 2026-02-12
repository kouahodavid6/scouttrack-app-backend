<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Identifiants ScoutTrack</title>
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
            padding: 0;
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
        }
        
        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 400;
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
        
        /* Box identifiants */
        .credentials-box {
            background: linear-gradient(to right, #F3F4F6, #FFFFFF);
            border: 2px solid #E5E7EB;
            border-left: 6px solid #7C3AED;
            padding: 25px;
            margin: 30px 0;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .credentials-title {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .credentials-title:before {
            content: "🔐";
            font-size: 24px;
        }
        
        .credential-item {
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .credential-label {
            font-weight: 600;
            color: #4B5563;
            min-width: 100px;
            font-size: 15px;
        }
        
        .credential-value {
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            background-color: #F9FAFB;
            padding: 10px 15px;
            border-radius: 8px;
            color: #1F2937;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid #E5E7EB;
            flex: 1;
            word-break: break-all;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
        }
        
        /* Bouton de connexion */
        .login-button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .login-button {
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
        
        .login-button:hover {
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
            
            .credentials-box {
                padding: 20px;
                margin: 25px 0;
            }
            
            .credential-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .credential-label {
                min-width: auto;
            }
            
            .credential-value {
                width: 100%;
            }
            
            .login-button {
                padding: 14px 30px;
                font-size: 16px;
                width: 100%;
            }
            
            .steps-container {
                padding: 20px;
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
            
            .credentials-title {
                font-size: 18px;
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
            <h2 class="header-title">Identifiants de connexion</h2>
            <p class="header-subtitle">Votre accès à la plateforme ScoutTrack</p>
        </div>
        
        <!-- Contenu principal -->
        <div class="email-content">
            <!-- Salutation -->
            <p class="greeting">
                Bonjour <strong>{{ $nom }}</strong>,
                <span class="entity-badge">{{ $entityType }}</span>
            </p>
            
            <!-- Message d'introduction -->
            <p class="message">
                Félicitations ! Votre compte {{ $entityType }} a été créé avec succès sur la plateforme ScoutTrack.
                Vous trouverez ci-dessous vos identifiants de connexion.
            </p>
            
            <!-- Box identifiants -->
            <div class="credentials-box">
                <h2 class="credentials-title">Vos identifiants de connexion</h2>
                
                <div class="credential-item">
                    <span class="credential-label">📧 Email :</span>
                    <span class="credential-value">{{ $email }}</span>
                </div>
                
                <div class="credential-item">
                    <span class="credential-label">🔑 Mot de passe :</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>
            
            <!-- Bouton de connexion -->
            <div class="login-button-container">
                <a href="https://scouttrack-app.vercel.app/login" class="login-button">
                    🚀 Se connecter maintenant
                </a>
                <p style="margin-top: 12px; color: #6B7280; font-size: 14px;">
                    <em>Cliquez sur ce bouton pour accéder directement à la connexion</em>
                </p>
            </div>
            
            <!-- Warning -->
            <div class="warning-box">
                <div class="warning-icon">⚠️</div>
                <div class="warning-text">
                    <strong>Sécurité importante :</strong> Ce mot de passe est temporaire. 
                    Pour protéger votre compte, veuillez le changer dès votre première connexion.
                    Ne partagez jamais vos identifiants avec qui que ce soit.
                </div>
            </div>
            
            <!-- Étapes de connexion -->
            <div class="steps-container">
                <h3 class="steps-title">Guide de connexion rapide</h3>
                <ol class="steps-list">
                    <li>Cliquez sur le bouton <strong>"Se connecter maintenant"</strong> ci-dessus</li>
                    <li>Entrez votre email : <strong>{{ $email }}</strong></li>
                    <li>Saisissez le mot de passe : <strong>{{ $password }}</strong></li>
                    <li>Accédez à <strong>Mon Profil → Sécurité</strong> pour modifier votre mot de passe</li>
                    <li>Explorez toutes les fonctionnalités de ScoutTrack !</li>
                </ol>
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
                <p class="signature-text">Nous sommes heureux de vous accueillir parmi nous !</p>
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
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            </div>
        </div>
    </div>
</body>
</html>