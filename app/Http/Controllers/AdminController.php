<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Retourne une réponse de succès standardisée
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
     * Retourne une réponse d'erreur standardisée
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
     * @OA\Get(
     *     path="/admin/dashboard",
     *     tags={"Admin"},
     *     summary="Dashboard administrateur",
     *     description="Récupérer les statistiques générales de la plateforme pour le dashboard admin",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", ref="#/components/schemas/AdminDashboard"),
     *             @OA\Property(property="message", type="string", example="Dashboard chargé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Admin uniquement",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="error", type="string", example="Accès refusé"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Vous devez être administrateur")
     *         )
     *     )
     * )
     */
    public function dashboard()
    {
        try {
            $totalUsers = User::count();
            $newUsersThisWeek = User::where('created_at', '>=', now()->subWeek())->count();
            
            $activePosts = Post::count();
            $newPostsToday = Post::whereDate('created_at', today())->count();
            
            $pendingReports = Report::where('status', Report::STATUS_PENDING)->count();
            
            $totalComments = Comment::count();
            $newCommentsToday = Comment::whereDate('created_at', today())->count();

            $stats = [
                'total_users' => [
                    'count' => $totalUsers,
                    'delta' => $newUsersThisWeek,
                    'delta_label' => 'this week'
                ],
                'active_posts' => [
                    'count' => $activePosts,
                    'delta' => $newPostsToday,
                    'delta_label' => 'today'
                ],
                'pending_reports' => [
                    'count' => $pendingReports,
                    'status' => $pendingReports > 20 ? 'requires_attention' : 'normal'
                ],
                'total_comments' => [
                    'count' => $totalComments,
                    'delta' => $newCommentsToday,
                    'delta_label' => 'today'
                ],
                'details' => [
                    'users' => [
                        'total' => $totalUsers,
                        'active' => User::where('is_active', true)->count(),
                        'inactive' => User::where('is_active', false)->count(),
                        'admins' => User::where('role', 'admin')->count(),
                        'new_today' => User::whereDate('created_at', today())->count(),
                        'new_this_week' => $newUsersThisWeek,
                    ],
                    'posts' => [
                        'total' => $activePosts,
                        'this_month' => Post::whereMonth('created_at', now()->month)->count(),
                        'today' => $newPostsToday,
                    ],
                    'reports' => [
                        'total' => Report::count(),
                        'pending' => $pendingReports,
                        'resolved' => Report::where('status', Report::STATUS_RESOLVED)->count(),
                        'rejected' => Report::where('status', Report::STATUS_REJECTED)->count(),
                    ],
                    'engagement' => [
                        'comments_total' => $totalComments,
                        'comments_today' => $newCommentsToday,
                        'reactions_total' => Reaction::count(),
                        'reactions_today' => Reaction::whereDate('created_at', today())->count(),
                        'likes_total' => Reaction::where('type', 'like')->count(),
                        'dislikes_total' => Reaction::where('type', 'dislike')->count(),
                    ]
                ]
            ];

            return $this->successResponse($stats, 'Dashboard chargé avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors du chargement du dashboard', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/users",
     *     tags={"Admin"},
     *     summary="Lister tous les utilisateurs",
     *     description="Récupérer la liste paginée des utilisateurs avec filtres et statistiques",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"active","inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query", 
     *         description="Filtrer par rôle",
     *         @OA\Schema(type="string", enum={"user","admin"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom ou email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
    public function users(Request $request)
    {
        try {
            $query = User::withCount(['posts', 'comments', 'reactions', 'reports']);

            // Filtres
            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }
            
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            $users = $query->orderBy('created_at', 'desc')
                          ->paginate($request->per_page ?? 20);

            return $this->successResponse($users, 'Utilisateurs récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des utilisateurs', 500);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'is_active' => 'required|boolean',
                'admin_note' => 'nullable|string|max:500'
            ]);

            $user->update([
                'is_active' => $validated['is_active']
            ]);

            // Log de l'action admin
            \Log::info("Admin {$request->user()->id} a " . 
                      ($validated['is_active'] ? 'activé' : 'désactivé') . 
                      " l'utilisateur {$userId}. Note: " . ($validated['admin_note'] ?? 'Aucune'));

            return $this->successResponse([
                'user' => $user
            ], 'Statut utilisateur modifié avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la modification du statut', 500);
        }
    }

    /**
     * Lister tous les posts avec filtres et signalements
     */
    public function posts(Request $request)
    {
        try {
            $query = Post::with(['user:id,name,email'])
                        ->withCount(['comments', 'reactions', 'reports']);

            // Filtre par nombre de signalements
            if ($request->has('min_reports')) {
                $query->having('reports_count', '>=', $request->min_reports);
            }

            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('content', 'like', '%' . $request->search . '%');
                });
            }

            $posts = $query->orderBy('reports_count', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate($request->per_page ?? 20);

            return $this->successResponse($posts, 'Posts récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des posts', 500);
        }
    }

    /**
     * Supprimer un post (admin seulement)
     */
    public function deletePost(Request $request, $postId)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $post = Post::findOrFail($postId);
            
            // Supprimer les relations
            $post->comments()->delete();
            $post->ratings()->delete();
            $post->reports()->delete();
            
            $post->delete();

            // Log de l'action
            \Log::info("Admin {$request->user()->id} a supprimé le post {$postId}. Raison: {$validated['reason']}");

            return $this->successResponse(null, 'Post supprimé avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la suppression du post', 500);
        }
    }

    /**
     * Lister tous les signalements
     */
    public function reports(Request $request)
    {
        try {
            $query = Report::with(['post:id,title,user_id', 'reporter:id,name,email', 'post.user:id,name,email']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            $reports = $query->orderBy('created_at', 'desc')
                           ->paginate($request->per_page ?? 20);

            return $this->successResponse($reports, 'Signalements récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des signalements', 500);
        }
    }

    /**
     * Statistiques détaillées
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->period ?? '30'; // jours

            $stats = [
                'users_growth' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                    
                'posts_activity' => Post::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                    
                'reports_trends' => Report::selectRaw('DATE(created_at) as date, reason, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->groupBy('date', 'reason')
                    ->orderBy('date')
                    ->get(),
                    
                'top_contributors' => User::withCount('posts')
                    ->orderBy('posts_count', 'desc')
                    ->limit(10)
                    ->get(['id', 'name', 'posts_count']),
            ];

            return $this->successResponse($stats, 'Statistiques récupérées avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des statistiques', 500);
        }
    }
}