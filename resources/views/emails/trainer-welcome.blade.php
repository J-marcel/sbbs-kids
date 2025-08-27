<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur notre plateforme</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .welcome-message {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .credentials-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .password-box {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            text-align: center;
        }
        .password-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            border: 2px dashed #007bff;
        }
        .security-warning {
            background-color: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
        .info-section {
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🎓<img src="{{ asset('assets/images/logo.png') }}" alt="Logo {{ config('app.name') }}" style="max-width: 200px; height: auto;">
            </div>
            <h1 style="color: #007bff;">Bienvenue {{ $trainer->name }} !</h1>
        </div>

        <div class="welcome-message">
            <h2>🎉 Félicitations !</h2>
            <p>Votre compte formateur a été créé avec succès sur notre plateforme. Nous sommes ravis de vous accueillir dans notre équipe pédagogique.</p>
        </div>

        <div class="info-section">
            <h3>🔐 Vos identifiants de connexion</h3>
            <div class="password-box">
                <h4>Votre mot de passe temporaire :</h4>
                <div class="password-value">{{ $password }}</div>
                <p style="font-size: 14px; margin-top: 15px;">
                    <strong>⚠️ Important :</strong> Copiez ce mot de passe et conservez-le en lieu sûr.
                </p>
            </div>
        </div>

        <div class="security-warning">
            <h3>🔒 Sécurité importante</h3>
            <ul style="margin: 10px 0; text-align: left;">
                <li><strong>Changez votre mot de passe</strong> dès votre première connexion</li>
                <li><strong>Ne partagez jamais</strong> vos identifiants de connexion</li>
                <li><strong>Supprimez cet email</strong> après avoir noté votre mot de passe</li>
                <li><strong>Utilisez un mot de passe fort</strong> lors du changement</li>
            </ul>
        </div>

        <div class="info-section">
            <h3>📚 Prochaines étapes</h3>
            <ol>
                <li>Connectez-vous avec votre email et le mot de passe ci-dessus</li>
                <li><strong>Changez immédiatement votre mot de passe</strong> dans votre profil</li>
                <li>Complétez votre profil si nécessaire</li>
                <li>Explorez les fonctionnalités disponibles</li>
            </ol>
        </div>

        <div class="info-section" style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8;">
            <h3>🌐 Comment se connecter</h3>
            {{-- <p><strong>URL de connexion :</strong> <a href="{{ config('app.frontend_url') }}/login" target="_blank">{{ config('app.frontend_url') }}/login</a></p> --}}
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Mot de passe :</strong> Celui indiqué ci-dessus</p>
        </div>

        <div class="info-section" style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            <h3>❓ Besoin d'aide ?</h3>
            <p>Si vous rencontrez des difficultés ou si vous avez des questions, n'hésitez pas à nous contacter :</p>
            <ul>
                <li>📧 Email : support@votreplateforme.com</li>
                <li>📱 Téléphone : +225 XX XX XX XX XX</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>⚠️ Cet email contient des informations sensibles. Supprimez-le après avoir noté vos identifiants.</strong></p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
