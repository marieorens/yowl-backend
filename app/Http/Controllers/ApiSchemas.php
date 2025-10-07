<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="Utilisateur",
 *     description="Modèle utilisateur de base",
 *     @OA\Property(property="id", type="integer", example=1, description="Identifiant unique"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Nom complet"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Adresse email"),
 *     @OA\Property(property="birthday", type="string", format="date", example="1995-05-15", description="Date de naissance"),
 *     @OA\Property(property="role", type="string", enum={"user","admin"}, example="user", description="Rôle utilisateur"),
 *     @OA\Property(property="profile_pic", type="string", nullable=true, example="profile_123.jpg", description="Photo de profil"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Statut actif du compte"),
 *     @OA\Property(property="email_verified_at", type="string", format="datetime", nullable=true, example="2023-10-02T14:30:00Z", description="Date de vérification email"),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-02T14:30:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="UserProfile",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/User"),
 *         @OA\Schema(
 *             @OA\Property(property="statistics", type="object",
 *                 @OA\Property(property="posts_count", type="integer", example=5, description="Nombre de posts"),
 *                 @OA\Property(property="ratings_count", type="integer", example=12, description="Nombre de ratings donnés"),
 *                 @OA\Property(property="comments_count", type="integer", example=8, description="Nombre de commentaires"),
 *                 @OA\Property(property="average_rating_given", type="number", format="float", example=4.2, description="Note moyenne donnée")
 *             )
 *         )
 *     }
 * )
 * 
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     title="Post",
 *     description="Publication communautaire",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Mon premier projet Arduino"),
 *     @OA\Property(property="content", type="string", example="Voici la description détaillée de mon projet..."),
 *     @OA\Property(property="link", type="string", nullable=true, example="https://github.com/user/projet"),
 *     @OA\Property(property="photos", type="string", nullable=true, example="post_123.jpg,post_124.jpg"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-02T14:30:00Z"),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 * 
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     title="Commentaire",
 *     description="Commentaire hiérarchique",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Excellent travail ! Comment as-tu géré la partie electronique ?"),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="post_id", type="integer", example=1),
 *     @OA\Property(property="parent_comment_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-02T15:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-02T15:00:00Z"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="replies", type="array", @OA\Items(ref="#/components/schemas/Comment"))
 * )
 * 
 * @OA\Schema(
 *     schema="Reaction",
 *     type="object",
 *     title="Réaction",
 *     description="Réaction (like/dislike) sur un post",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"like","dislike"}, example="like", description="Type de réaction"),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="post_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-02T16:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-02T16:00:00Z"),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 * 
 * @OA\Schema(
 *     schema="Report",
 *     type="object",
 *     title="Signalement",
 *     description="Signalement de contenu",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="reason", type="string", enum={"spam","inappropriate","harassment","fake","other"}, example="spam"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Ce post contient du spam publicitaire"),
 *     @OA\Property(property="status", type="string", enum={"pending","reviewed","resolved","rejected"}, example="pending"),
 *     @OA\Property(property="reporter_user_id", type="integer", example=3),
 *     @OA\Property(property="post_id", type="integer", example=1),
 *     @OA\Property(property="resolved_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="admin_note", type="string", nullable=true, example=null),
 *     @OA\Property(property="resolved_at", type="string", format="datetime", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-02T17:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-02T17:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Catégorie",
 *     description="Catégorie de posts",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Électronique"),
 *     @OA\Property(property="description", type="string", example="Projets d'électronique et d'automatisation"),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2023-10-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2023-10-01T10:00:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="StandardResponse",
 *     type="object",
 *     title="Réponse Standard",
 *     description="Format de réponse standardisé",
 *     @OA\Property(property="status", type="string", enum={"success","error"}, example="success"),
 *     @OA\Property(property="error", oneOf={
 *         @OA\Schema(type="null"),
 *         @OA\Schema(type="string"),
 *         @OA\Schema(type="object")
 *     }, example=null),
 *     @OA\Property(property="data", oneOf={
 *         @OA\Schema(type="null"),
 *         @OA\Schema(type="object"),
 *         @OA\Schema(type="array", @OA\Items())
 *     }),
 *     @OA\Property(property="message", type="string", example="Opération réussie")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="Erreur de Validation",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="error", type="object",
 *         @OA\Property(property="field_name", type="array", @OA\Items(type="string", example="Ce champ est requis."))
 *     ),
 *     @OA\Property(property="data", type="null"),
 *     @OA\Property(property="message", type="string", example="Erreur de validation")
 * )
 * 
 * @OA\Schema(
 *     schema="AdminDashboard",
 *     type="object",
 *     title="Dashboard Admin",
 *     @OA\Property(property="users", type="object",
 *         @OA\Property(property="total", type="integer", example=1250),
 *         @OA\Property(property="active", type="integer", example=1180),
 *         @OA\Property(property="inactive", type="integer", example=70),
 *         @OA\Property(property="admins", type="integer", example=5)
 *     ),
 *     @OA\Property(property="posts", type="object",
 *         @OA\Property(property="total", type="integer", example=450),
 *         @OA\Property(property="this_month", type="integer", example=23),
 *         @OA\Property(property="average_rating", type="number", format="float", example=4.2)
 *     ),
 *     @OA\Property(property="reports", type="object",
 *         @OA\Property(property="total", type="integer", example=12),
 *         @OA\Property(property="pending", type="integer", example=3),
 *         @OA\Property(property="resolved", type="integer", example=9)
 *     ),
 *     @OA\Property(property="activity", type="object",
 *         @OA\Property(property="comments_today", type="integer", example=15),
 *         @OA\Property(property="ratings_today", type="integer", example=8),
 *         @OA\Property(property="new_users_today", type="integer", example=3)
 *     )
 * )
 */
class ApiSchemas
{
   
}