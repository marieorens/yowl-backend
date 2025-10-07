<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReactionController extends Controller
{
    private function successResponse($data = null, $message = 'Opération réussie', $statusCode = 200)
    {
        return response()->json([
            'status' => 'success',
            'error' => null,
            'data' => $data,
            'message' => $message
        ], $statusCode);
    }

    private function errorResponse($error = null, $message = 'Une erreur est survenue', $statusCode = 500)
    {
        return response()->json([
            'status' => 'error',
            'error' => $error,
            'data' => null,
            'message' => $message
        ], $statusCode);
    }

    public function getStats($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            
            $likesCount = Reaction::where('post_id', $postId)->where('type', 'like')->count();
            $dislikesCount = Reaction::where('post_id', $postId)->where('type', 'dislike')->count();
            
            $userReaction = null;
            if (auth()->check()) {
                $reaction = Reaction::where('post_id', $postId)
                    ->where('user_id', auth()->id())
                    ->first();
                $userReaction = $reaction ? $reaction->type : null;
            }

            return $this->successResponse([
                'post_id' => $postId,
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
                'user_reaction' => $userReaction
            ], 'Statistiques récupérées avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des statistiques', 500);
        }
    }

    public function store(Request $request, $postId)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:like,dislike',
            ]);

            $post = Post::findOrFail($postId);
            $userId = auth()->id();

            $existingReaction = Reaction::where('post_id', $postId)
                ->where('user_id', $userId)
                ->first();

            if ($existingReaction) {
                if ($existingReaction->type === $validated['type']) {
                    $existingReaction->delete();
                    $message = 'Réaction retirée avec succès';
                    $reaction = null;
                } else {
                    $existingReaction->update(['type' => $validated['type']]);
                    $message = 'Réaction modifiée avec succès';
                    $reaction = $existingReaction;
                }
            } else {
                $reaction = Reaction::create([
                    'post_id' => $postId,
                    'user_id' => $userId,
                    'type' => $validated['type'],
                ]);
                $message = 'Réaction ajoutée avec succès';
            }

            $likesCount = Reaction::where('post_id', $postId)->where('type', 'like')->count();
            $dislikesCount = Reaction::where('post_id', $postId)->where('type', 'dislike')->count();

            return $this->successResponse([
                'reaction' => $reaction,
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
            ], $message);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de l\'ajout de la réaction', 500);
        }
    }

    public function destroy($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            
            $reaction = Reaction::where('post_id', $postId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$reaction) {
                return $this->errorResponse('Aucune réaction trouvée', 'Vous n\'avez pas encore réagi à ce post', 404);
            }

            $reaction->delete();

            return $this->successResponse(null, 'Réaction supprimée avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la suppression de la réaction', 500);
        }
    }

    public function getUserReaction($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            
            $reaction = Reaction::where('post_id', $postId)
                ->where('user_id', auth()->id())
                ->first();

            return $this->successResponse([
                'reaction' => $reaction
            ], $reaction ? 'Réaction trouvée' : 'Aucune réaction');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération de la réaction', 500);
        }
    }
}
