<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\AgeRange;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * la fonction succesResponse renvoie une réponse de succès standardisée
     */
    private function successResponse($data = null, $message = 'Opération réussie', $statusCode = 200)
    {
        return response()->json([
            'status' => 'success',
            'error' => null,
            'data' => $data,
            'message' => $message
        ], $statusCode);
    }

    /**
     * lafonxtion errorResponse une réponse d'erreur standardisée
     */
    private function errorResponse($error = null, $message = 'Une erreur est survenue', $statusCode = 500)
    {
        return response()->json([
            'status' => 'error',
            'error' => $error,
            'data' => null,
            'message' => $message
        ], $statusCode);
    }
   
    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Authentication"},
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Créer un nouveau compte utilisateur avec validation d'âge (13-35 ans). Un email de vérification est envoyé automatiquement. Le compte reste inactif jusqu'à la vérification email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","birthday"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="Nom complet de l'utilisateur"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Adresse email unique"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Mot de passe (minimum 8 caractères)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Confirmation du mot de passe"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1995-05-15", description="Date de naissance (13-35 ans requis)"),
     *             @OA\Property(property="role", type="string", enum={"user","admin"}, example="user", description="Rôle utilisateur (optionnel, défaut: user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie - Email de vérification envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=false),
     *                     @OA\Property(property="email_verified", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="message", type="string", example="Un email de confirmation a été envoyé à votre adresse.")
     *             ),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès. Vérifiez votre email pour l'activer.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'birthday' => ['required', 'date', new AgeRange],
                'role' => 'nullable|string|in:user,admin',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'birthday' => $validated['birthday'] ?? null,
                'role' => $validated['role'] ?? 'user',
                'is_active' => false, 
            ]);

            // jénérer le token de vérification d'email
            $user->generateEmailVerificationToken();

            // Envoi demail de confirmation
            $mailService = new \App\Services\MailService();
            $mailService->sendEmailVerification($user);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'email_verified' => false
                ],
                'message' => 'Un email de confirmation a été envoyé à votre adresse.'
            ], 'Compte créé avec succès. Vérifiez votre email pour l\'activer.', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la création du compte', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Authentication"},
     *     summary="Connexion utilisateur",
     *     description="Authentifier un utilisateur avec email et mot de passe. Le compte doit être vérifié et actif pour se connecter.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string", example="2|xyz789..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             ),
     *             @OA\Property(property="message", type="string", example="Connexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="error", type="string", example="Identifiants incorrects"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Identifiants incorrects")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte non vérifié ou désactivé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="error", type="string", example="Email non confirmé"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Vous devez confirmer votre adresse email avant de vous connecter.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($validated)) {
                return $this->errorResponse('Identifiants incorrects', 'Identifiants incorrects', 401);
            }

            $user = User::where('email', $validated['email'])->firstOrFail();

            // Vérifier si l'email est confirmé
            if (!$user->hasVerifiedEmail()) {
                return $this->errorResponse(
                    'Email non confirmé', 
                    'Vous devez confirmer votre adresse email avant de vous connecter. Vérifiez votre boîte mail.', 
                    403
                );
            }

            // Vérifier si le compte est actif
            if (!$user->isActive()) {
                return $this->errorResponse(
                    'Compte désactivé', 
                    'Votre compte a été désactivé. Contactez l\'administrateur.', 
                    403
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Connexion réussie');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la connexion', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Authentication"},
     *     summary="Déconnexion utilisateur",
     *     description="Déconnecter l'utilisateur et révoquer le token d'accès",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la déconnexion', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/user",
     *     tags={"Users"},
     *     summary="Récupérer le profil de l'utilisateur connecté",
     *     description="Obtenir les informations complètes du profil utilisateur avec statistiques",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserProfile")
     *             ),
     *             @OA\Property(property="message", type="string", example="Profil récupéré avec succès")
     *         )
     *     )
     * )
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();
            $user->load(['posts', 'ratings', 'comments']);
            
            return $this->successResponse([
                'user' => $user,
                'statistics' => [
                    'posts_count' => $user->posts->count(),
                    'ratings_count' => $user->ratings->count(),
                    'comments_count' => $user->comments->count(),
                    'average_rating_given' => round($user->ratings->avg('rating') ?? 0, 2),
                ]
            ], 'Profil récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération du profil', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/users/{user}/profile",
     *     tags={"Users"},
     *     summary="Voir le profil public d'un utilisateur",
     *     description="Récupérer le profil public d'un utilisateur avec ses statistiques et posts récents",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID de l'utilisateur",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil utilisateur récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="profile_pic", type="string", nullable=true, example="profile.jpg"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="posts_count", type="integer", example=15),
     *                     @OA\Property(property="ratings_count", type="integer", example=25),
     *                     @OA\Property(property="comments_count", type="integer", example=30)
     *                 ),
     *                 @OA\Property(property="recent_posts", type="array", @OA\Items(ref="#/components/schemas/Post"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Profil utilisateur récupéré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     )
     * )
     */
    public function showUserProfile($userId)
    {
        try {
            $user = User::with(['posts' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])->findOrFail($userId);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_pic' => $user->profile_pic,
                    'created_at' => $user->created_at,
                ],
                'statistics' => [
                    'posts_count' => $user->posts()->count(),
                    'ratings_count' => $user->ratings()->count(),
                    'comments_count' => $user->comments()->count(),
                ],
                'recent_posts' => $user->posts
            ], 'Profil utilisateur récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Utilisateur non trouvé', 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/user/profile",
     *     tags={"Users"},
     *     summary="Mettre à jour le profil utilisateur",
     *     description="Modifier les informations du profil de l'utilisateur connecté",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe Updated", description="Nom complet (optionnel)"),
     *             @OA\Property(property="email", type="string", format="email", example="newemail@example.com", description="Nouvelle adresse email (optionnel)"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1995-05-15", description="Date de naissance (optionnel)"),
     *             @OA\Property(property="profile_pic", type="string", nullable=true, example="new_profile.jpg", description="Nom du fichier de photo de profil (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             ),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'birthday' => ['sometimes', 'nullable', 'date', new AgeRange],
                'profile_pic' => 'sometimes|nullable|string',
            ]);

            $user->update($validated);

            return $this->successResponse([
                'user' => $user->fresh()
            ], 'Profil mis à jour avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la mise à jour du profil', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/user/password",
     *     tags={"Users"},
     *     summary="Changer le mot de passe",
     *     description="Modifier le mot de passe de l'utilisateur connecté",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123", description="Mot de passe actuel"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123", description="Nouveau mot de passe (minimum 8 caractères)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="Confirmation du nouveau mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe changé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Mot de passe changé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Mot de passe actuel incorrect",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('Le mot de passe actuel est incorrect', 'Le mot de passe actuel est incorrect', 400);
            }

            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            return $this->successResponse(null, 'Mot de passe changé avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors du changement de mot de passe', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/email/verify",
     *     tags={"Authentication"},
     *     summary="Confirmer l'adresse email",
     *     description="Valider l'adresse email avec le token reçu par email et activer le compte",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="abc123token456", description="Token de vérification reçu par email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email confirmé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="email_verified", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Email confirmé avec succès ! Votre compte est maintenant actif.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function verifyEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string'
            ]);

            $user = User::where('email_verification_token', $validated['token'])->first();

            if (!$user) {
                return $this->errorResponse('Token invalide', 'Le lien de confirmation est invalide ou a expiré.', 400);
            }

            // Marquer l'email comme vérifié activer le compte
            $user->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'is_active' => true
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => true,
                    'email_verified' => true
                ]
            ], 'Email confirmé avec succès ! Votre compte est maintenant actif.');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la confirmation', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/email/resend",
     *     tags={"Authentication"},
     *     summary="Renvoyer un email de confirmation",
     *     description="Renvoyer un nouvel email de vérification pour un compte non confirmé",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Adresse email du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de confirmation renvoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Email de confirmation renvoyé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email déjà confirmé",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function resendVerificationEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $validated['email'])->first();

            if ($user->hasVerifiedEmail()) {
                return $this->errorResponse('Email déjà confirmé', 'Cet email est déjà confirmé.', 400);
            }

            // Générer un nouveau token et renvoyer l'email
            $user->generateEmailVerificationToken();
            
            $mailService = new \App\Services\MailService();
            $mailService->sendEmailVerification($user);

            return $this->successResponse(null, 'Email de confirmation renvoyé avec succès.');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de l\'envoi', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/password/reset/request",
     *     tags={"Authentication"},
     *     summary="Demander la réinitialisation du mot de passe",
     *     description="Envoyer un email avec un lien de réinitialisation de mot de passe",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Adresse email du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de réinitialisation envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Un email de réinitialisation a été envoyé à votre adresse.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function requestPasswordReset(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $validated['email'])->first();

            // Générer le token de reset
            $user->generatePasswordResetToken();

            // Envoyer l'email de reset
            $mailService = new \App\Services\MailService();
            $mailService->sendPasswordReset($user);

            return $this->successResponse(null, 'Un email de réinitialisation a été envoyé à votre adresse.');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de l\'envoi', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/password/reset",
     *     tags={"Authentication"},
     *     summary="Réinitialiser le mot de passe",
     *     description="Valider le token et définir un nouveau mot de passe",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", example="reset123token456", description="Token de réinitialisation reçu par email"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123", description="Nouveau mot de passe (minimum 8 caractères)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="Confirmation du nouveau mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token invalide ou expiré",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $user = User::where('password_reset_token', $validated['token'])->first();

            if (!$user || !$user->isValidPasswordResetToken($validated['token'])) {
                return $this->errorResponse('Token invalide', 'Le lien de réinitialisation est invalide ou a expiré.', 400);
            }

            // Réinitialiser le mot de passe
            $user->update([
                'password' => Hash::make($validated['password']),
                'password_reset_token' => null,
                'password_reset_expires' => null
            ]);

            $user->tokens()->delete();

            return $this->successResponse(null, 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la réinitialisation', 500);
        }
    }
}