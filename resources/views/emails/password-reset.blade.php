<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; }
        .reset-button { display: inline-block; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; transition: transform 0.2s; }
        .reset-button:hover { transform: translateY(-2px); }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .security-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .warning { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Réinitialisation du mot de passe</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $userName }}</strong>,</p>
            
            <p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte {{ config('app.name') }}.</p>
            
            <p>Pour créer un nouveau mot de passe, cliquez sur le bouton ci-dessous :</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $resetUrl }}" class="reset-button">
                     Réinitialiser mon mot de passe
                </a>
            </div>

            <div class="warning-box">
                <p><strong> Important :</strong></p>
                <ul>
                    <li>Ce lien est valable pendant <strong>1 heure</strong> seulement</li>
                    <li>Le lien ne peut être utilisé qu'<strong>une seule fois</strong></li>
                    <li>Après utilisation, il sera automatiquement désactivé</li>
                </ul>
            </div>

            <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
            <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;">
                {{ $resetUrl }}
            </p>

            <div class="security-box">
                <p><strong>Conseils de sécurité :</strong></p>
                <ul>
                    <li>Utilisez un mot de passe fort (8+ caractères, majuscules, minuscules, chiffres)</li>
                    <li>Ne réutilisez pas un ancien mot de passe</li>
                    <li>Ne partagez jamais votre mot de passe avec personne</li>
                    <li>Considérez l'utilisation d'un gestionnaire de mots de passe</li>
                </ul>
            </div>

            <p><strong>Vous n'avez pas demandé cette réinitialisation ?</strong></p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email. Votre mot de passe actuel reste inchangé et votre compte est sécurisé.</p>

            <p>Pour toute question de sécurité, contactez notre support.</p>
            
            <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>{{ config('app.name') }} - Votre plateforme communautaire</p>
            <p class="warning"> Ne partagez jamais ce lien avec d'autres personnes</p>
        </div>
    </div>
</body>
</html>