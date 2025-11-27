<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirection - SBBS Kids</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .success { color: #10b981; }
        .pending { color: #f59e0b; }
        .failed { color: #ef4444; }
        .error { color: #ef4444; }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1f2937;
        }

        p {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .loader {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 14px;
            color: #4b5563;
        }

        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($status === 'subscription_success' || $status === 'workshop_success')
            <div class="icon success">✅</div>
            <h1>Paiement réussi !</h1>
            <p>{{ $data['message'] ?? 'Votre transaction a été effectuée avec succès.' }}</p>

        @elseif($status === 'pending')
            <div class="icon pending">⏳</div>
            <h1>Paiement en cours</h1>
            <p>{{ $data['message'] ?? 'Votre paiement est en cours de traitement.' }}</p>

        @elseif($status === 'failed')
            <div class="icon failed">❌</div>
            <h1>Paiement échoué</h1>
            <p>{{ $data['message'] ?? 'Le paiement n\'a pas pu être effectué.' }}</p>

        @else
            <div class="icon error">⚠️</div>
            <h1>Erreur</h1>
            <p>{{ $data['message'] ?? 'Une erreur est survenue.' }}</p>
        @endif

        <div class="loader"></div>
        <p style="font-size: 14px; color: #9ca3af;">Redirection vers l'application...</p>

        @if(isset($data['transaction_id']))
            <div class="info">
                <strong>Transaction ID:</strong><br>
                {{ $data['transaction_id'] }}
            </div>
        @endif

        <a href="{{ $fallbackUrl }}" class="btn" id="manualBtn" style="display:none;">
            Ouvrir manuellement
        </a>
    </div>

    <script>
        // ✅ Configuration
        const deepLink = @json($deepLink);
        const iosAppId = @json($iosAppId);
        const androidPackage = @json($androidPackage);
        const fallbackUrl = @json($fallbackUrl);

        // ✅ Détection de la plateforme
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
        const isAndroid = /android/i.test(userAgent);

        // ✅ Fonction de redirection
        function redirectToApp() {
            console.log('Tentative de redirection vers:', deepLink);

            if (isIOS) {
                // iOS: Essayer le deep link, puis l'App Store
                window.location.href = deepLink;

                setTimeout(() => {
                    if (!document.hidden) {
                        // L'app n'est pas installée, rediriger vers l'App Store
                        window.location.href = `https://apps.apple.com/app/${iosAppId}`;
                    }
                }, 2000);

            } else if (isAndroid) {
                // Android: Utiliser l'Intent
                const intent = `intent://payment/{{ $status }}?data={{ base64_encode(json_encode($data)) }}#Intent;scheme={{ config('app.mobile_scheme', 'sbbskids') }};package=${androidPackage};end`;
                window.location.href = intent;

                setTimeout(() => {
                    if (!document.hidden) {
                        // L'app n'est pas installée, rediriger vers le Play Store
                        window.location.href = `https://play.google.com/store/apps/details?id=${androidPackage}`;
                    }
                }, 2000);

            } else {
                // Desktop ou autre: Redirection simple
                window.location.href = deepLink;

                setTimeout(() => {
                    document.getElementById('manualBtn').style.display = 'inline-block';
                }, 3000);
            }
        }

        // ✅ Lancer la redirection automatiquement après 1 seconde
        setTimeout(redirectToApp, 1000);

        // ✅ Permettre la redirection manuelle si nécessaire
        document.getElementById('manualBtn').addEventListener('click', (e) => {
            e.preventDefault();
            redirectToApp();
        });
    </script>
</body>
</html>
