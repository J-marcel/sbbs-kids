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
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #0056b3;
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
            <h3>📋 Informations de votre compte :</h3>
            <div class="credentials-box">
                <p><strong>Nom :</strong> {{ $trainer->name }}</p>
                <p><strong>Email :</strong> {{ $user->email }}</p>
                <p><strong>Téléphone :</strong> {{ $trainer->phone_number }}</p>
                @if($trainer->number_whatsapp)
                    <p><strong>WhatsApp :</strong> {{ $trainer->number_whatsapp }}</p>
                @endif
                <p><strong>Genre :</strong> {{ $trainer->gender }}</p>
            </div>
        </div>

        <div class="info-section">
            <h3>🔐 Configuration de votre mot de passe</h3>
            <p>Pour sécuriser votre compte, vous devez définir votre propre mot de passe. Cliquez sur le bouton ci-dessous pour créer votre mot de passe personnalisé :</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">
                    🔑 Définir mon mot de passe
                </a>
            </div>

            <p style="font-size: 14px; color: #666;">
                <strong>Note :</strong> Ce lien est valide pendant 60 minutes. Si le lien expire, vous pourrez demander un nouveau lien de réinitialisation depuis la page de connexion.
            </p>
        </div>

        <div class="info-section">
            <h3>📚 Prochaines étapes</h3>
            <ol>
                <li>Cliquez sur le lien ci-dessus pour définir votre mot de passe</li>
                <li>Connectez-vous à votre espace formateur</li>
                <li>Complétez votre profil si nécessaire</li>
                <li>Explorez les fonctionnalités disponibles</li>
            </ol>
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
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
