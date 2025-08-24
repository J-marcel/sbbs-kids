<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification de votre compte</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f8fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-height: 60px;
        }
        h1 {
            color: #333333;
            font-size: 24px;
            text-align: center;
        }
        p {
            color: #555555;
            font-size: 16px;
            line-height: 1.6;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
            background-color: #1e88e5;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            letter-spacing: 5px;
        }
        .button {
            display: inline-block;
            background-color: #1e88e5;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #999999;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="email-container">

        <!-- Logo -->
        <div class="logo">
            {{-- <img src="{{ url('storage/images/artisan_logo.jpg') }}" alt="Logo {{ config('app.name') }}" style="max-width: 200px; height: auto;"> --}}
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo {{ config('app.name') }}" style="max-width: 200px; height: auto;">
        </div>

        <h1>Bonjour {{ $user->name }},</h1>

        <p>
            Nous vous remercions pour votre inscription sur <strong style="color:#1e88e5; font-weight: bold;">
        {{ config('app.name') }}
    </strong>.
            <br><br>
            Afin de finaliser la création de votre compte, veuillez saisir le code de vérification ci-dessous :
        </p>

        <div class="otp-code">
            {{ $otp }}
        </div>

        <p>
            Ce code est <strong>personnel</strong> et <strong>valide pendant 15 minutes</strong> à compter de la réception de ce message.
            <br><br>
            Si vous n’êtes pas à l’origine de cette demande, vous pouvez simplement ignorer ce courriel.
        </p>

        <p>
            Pour toute question ou assistance, n’hésitez pas à nous contacter :
        </p>

        <div style="text-align: center;">
            <a href="mailto:support@{{ request()->getHost() }}" class="button" style="color: #ffffff;">Contacter le support</a>
        </div>

        <div class="footer">
            Cordialement,<br>
            L’équipe {{ config('app.name') }}
        </div>
    </div>
</body>
</html>

