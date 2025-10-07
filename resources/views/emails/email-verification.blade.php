<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmez votre adresse email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 18px; margin-bottom: 20px; }
        .verify-button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; transition: transform 0.2s; }
        .verify-button:hover { transform: translateY(-2px); }
        .info-box { background: #e8f4fd; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .warning { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Bienvenue sur {{ config('app.name') }} !</h1>
        </div>

        <div class="content">
            <p class="welcome-text">Bonjour <strong>{{ $userName }}</strong>,</p>
            
            <p>Merci d'avoir rejoint notre communauté ! Nous sommes ravis de vous accueillir parmi nous.</p>
            
            <p>Pour finaliser votre inscription et activer votre compte, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $verificationUrl }}" class="verify-button">
                     Confirmer mon adresse email
                </a>
            </div>

            <div class="info-box">
                <p><strong>ℹImportant :</strong></p>
                <ul>
                    <li>Ce lien est valable pendant <strong>24 heures</strong></li>
                    <li>Vous ne pourrez pas vous connecter tant que votre email n'est pas confirmé</li>
                    <li>Si vous n'avez pas créé de compte, ignorez cet email</li>
                </ul>
            </div>

            <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
            <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;">
                {{ $verificationUrl }}
            </p>

            <p>Une fois votre email confirmé, vous pourrez :</p>
            <ul>
                <li>✅ Créer et partager des posts</li>
                <li>✅ Commenter et évaluer les publications</li>
                <li>✅ Interagir avec la communauté</li>
                <li>✅ Personnaliser votre profil</li>
            </ul>

            <p>À très bientôt sur la plateforme !</p>
            
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