<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Makers Community API",
 *     version="1.0.0",
 *     description="API complète pour la plateforme communautaire Makers Community avec authentification, posts, commentaires, ratings et modération automatique. Cette API permet de gérer une communauté de makers avec toutes les fonctionnalités nécessaires.",
 *     @OA\Contact(
 *         email="dev@makerscomm.com",
 *         name="Équipe Makers Community"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement local"
 * )
 * 
 * @OA\Server(
 *     url="https://api.makerscomm.com/api", 
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",  
 *     bearerFormat="JWT",
 *     description="Token d'authentification Laravel Sanctum. Format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Gestion de l'authentification utilisateur - Inscription, connexion, vérification email"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="Gestion des profils utilisateurs et statistiques personnelles"
 * )
 * 
 * @OA\Tag(
 *     name="Posts",
 *     description="Gestion des posts de la communauté avec catégories et images"
 * )
 * 
 * @OA\Tag(
 *     name="Comments", 
 *     description="Système de commentaires hiérarchique avec réponses"
 * )
 * 
 * @OA\Tag(
 *     name="Ratings",
 *     description="Système de notation des posts avec statistiques automatiques"
 * )
 * 
 * @OA\Tag(
 *     name="Reports",
 *     description="Système de signalement et modération automatique"
 * )
 * 
 * @OA\Tag(
 *     name="Images",
 *     description="Gestion et upload d'images avec optimisation automatique"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Back-office administrateur avec dashboard et modération"
 * )
 */
abstract class Controller
{
    //
}
