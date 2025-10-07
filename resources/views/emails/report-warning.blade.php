<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isAccountDeactivated ? 'Compte désactivé' : 'Avertissement' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; }
        .danger { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ config('app.name') }}</h2>
        </div>

        <p>Bonjour {{ $userName }},</p>

        @if($isAccountDeactivated)
            <div class="danger">
                <h3> Votre compte a été désactivé</h3>
                <p>Votre post "<strong>{{ $postTitle }}</strong>" a reçu <strong>{{ $reportCount }} signalements</strong>.</p>
                <p>En raison du nombre élevé de signalements, votre compte a été temporairement désactivé.</p>
                <p><strong>Actions à entreprendre :</strong></p>
                <ul>
                    <li>Contactez notre équipe de modération</li>
                    <li>Révisez nos conditions d'utilisation</li>
                    <li>Votre compte pourra être réactivé après examen</li>
                </ul>
            </div>
        @else
            <div class="warning">
                <h3>Avertissement - Post signalé</h3>
                <p>Votre post "<strong>{{ $postTitle }}</strong>" a reçu <strong>{{ $reportCount }} signalements</strong>.</p>
                <p>Nous vous invitons à :</p>
                <ul>
                    <li>Vérifier que votre contenu respecte nos règles communautaires</li>
                    <li>Modifier ou supprimer le post si nécessaire</li>
                    <li>Éviter ce type de contenu à l'avenir</li>
                </ul>
                <p><em>Attention : Si votre post atteint 5 signalements, votre compte sera automatiquement désactivé.</em></p>
            </div>
        @endif

        <p>Si vous pensez qu'il s'agit d'une erreur, n'hésitez pas à nous contacter.</p>

        <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>{{ config('app.name') }} - Plateforme communautaire</p>
        </div>
    </div>
</body>
</html>