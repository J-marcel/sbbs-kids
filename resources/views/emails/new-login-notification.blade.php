<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle connexion détectée</title>
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
        .info-box {
            background-color: #e3f2fd;
            border-left: 5px solid #1e88e5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .info-box strong {
            display: block;
            color: #0d47a1;
            margin-bottom: 5px;
        }
        .device-info {
            margin-top: 10px;
        }
        .device-info-item {
            margin-bottom: 5px;
            color: #0d47a1;
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
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo {{ config('app.name') }}">
        </div>

        <h1>Bonjour {{ $user->name }},</h1>

        <p>
            Une <strong>nouvelle connexion</strong> à votre compte <strong>{{ config('app.name') }}</strong> a été détectée.
        </p>

        <div class="info-box">
            <strong>Adresse IP :</strong>
            {{ $ip }}

            @if($location)
                <strong>Localisation :</strong>
                {{ $location }}
            @else
                <strong>Localisation :</strong>
                Indisponible
            @endif

            @if(is_array($deviceInfo))
                <div class="device-info">
                    <strong>Informations sur l'appareil :</strong>
                    <div class="device-info-item">Système : {{ $deviceInfo['plateforme'] ?? 'Inconnu' }}</div>
                    <div class="device-info-item">Navigateur : {{ $deviceInfo['navigateur'] ?? 'Inconnu' }} {{ $deviceInfo['version'] ?? '' }}</div>
                    <div class="device-info-item">Type d'appareil : {{ $deviceInfo['appareil'] ?: 'Ordinateur' }}</div>
                </div>
            @elseif($deviceInfo)
                <div class="device-info">
                    <strong>Informations sur l'appareil :</strong>
                    <div class="device-info-item">{{ $deviceInfo }}</div>
                </div>
            @endif
        </div>

        <p>
            Si cette connexion vous semble familière, vous pouvez ignorer ce message.
        </p>

        <p>
            <strong>Si ce n'était pas vous</strong>, nous vous recommandons de <strong>changer votre mot de passe immédiatement</strong> pour sécuriser votre compte.
        </p>

        <p>
            Pour toute question ou assistance, n'hésitez pas à nous contacter :
        </p>

        <div style="text-align: center; margin-top: 20px;">
            <a href="mailto:support@{{ request()->getHost() }}" style="
                display: inline-block;
                background-color: #1e88e5;
                color: #ffffff;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-weight: bold;
            ">Contacter le support</a>
        </div>

        <div class="footer">
            Cordialement,<br>
            L'équipe {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
