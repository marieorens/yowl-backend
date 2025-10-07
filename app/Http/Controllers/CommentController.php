<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
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

   
    /**
     * @OA\Get(
     *     path="/posts/{post}/comments",
     *     tags={"Comments"},
     *     summary="Lister les commentaires d'un post",
     *     description="Récupérer tous les commentaires principaux d'un post avec leurs réponses (structure hiérarchique)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         description="ID du post",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commentaires récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="post_id", type="integer", example=1),
     *                 @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/Comment")),
     *                 @OA\Property(property="total_comments", type="integer", example=5)
     *             ),
     *             @OA\Property(property="message", type="string", example="Commentaires récupérés avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     )
     * )
     */
    public function index($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            
            $comments = Comment::where('post_id', $postId)
                ->with(['user:id,name,profile_pic', 'children.user:id,name,profile_pic'])
                ->whereNull('parent_id') 
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse([
                'post_id' => $postId,
                'comments' => $comments,
                'total_comments' => Comment::where('post_id', $postId)->count()
            ], 'Commentaires récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des commentaires', 500);
        }
    }

   
    /**
     * @OA\Post(
     *     path="/posts/{post}/comments",
     *     tags={"Comments"},
     *     summary="Créer un nouveau commentaire",
     *     description="Ajouter un commentaire à un post. Peut être un commentaire principal ou une réponse à un autre commentaire.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         description="ID du post",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Excellent projet ! Comment avez-vous géré la partie hardware ?", description="Contenu du commentaire (max 1000 caractères)"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID du commentaire parent pour une réponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Commentaire créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="comment", ref="#/components/schemas/Comment")
     *             ),
     *             @OA\Property(property="message", type="string", example="Commentaire ajouté avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function store(Request $request, $postId)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'parent_id' => 'nullable|exists:comments,id'
            ]);

            
            $post = Post::findOrFail($postId);

           
            if ($validated['parent_id']) {
                $parentComment = Comment::findOrFail($validated['parent_id']);
                if ($parentComment->post_id != $postId) {
                    return $this->errorResponse('Le commentaire parent n\'appartient pas à ce post', 'Erreur de validation', 422);
                }
            }

            $comment = Comment::create([
                'post_id' => $postId,
                'user_id' => $request->user()->id,
                'content' => $validated['content'],
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            $comment->load('user:id,name,profile_pic');

            return $this->successResponse([
                'comment' => $comment
            ], 'Commentaire créé avec succès', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la création du commentaire', 500);
        }
    }

   
    public function show($postId, $id)
    {
        try {
            $comment = Comment::where('post_id', $postId)
                ->with(['user:id,name,profile_pic', 'children.user:id,name,profile_pic'])
                ->findOrFail($id);

            return $this->successResponse([
                'comment' => $comment
            ], 'Commentaire récupéré avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Commentaire non trouvé', 404);
        }
    }

  
    public function update(Request $request, $postId, $id)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000'
            ]);

            $comment = Comment::where('post_id', $postId)->findOrFail($id);

           
            if ($comment->user_id !== $request->user()->id) {
                return $this->errorResponse('Vous n\'êtes pas autorisé à modifier ce commentaire', 'Accès refusé', 403);
            }

            $comment->update($validated);
            $comment->load('user:id,name,profile_pic');

            return $this->successResponse([
                'comment' => $comment
            ], 'Commentaire modifié avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la modification du commentaire', 500);
        }
    }

   
    public function destroy(Request $request, $postId, $id)
    {
        try {
            $comment = Comment::where('post_id', $postId)->findOrFail($id);

            if ($comment->user_id !== $request->user()->id) {
                return $this->errorResponse('Vous n\'êtes pas autorisé à supprimer ce commentaire', 'Accès refusé', 403);
            }

            Comment::where('parent_id', $id)->delete();
            $comment->delete();

            return $this->successResponse(null, 'Commentaire supprimé avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la suppression du commentaire', 500);
        }
    }

    public function replies($postId, $commentId)
    {
        try {
            $comment = Comment::where('post_id', $postId)->findOrFail($commentId);
            
            $replies = Comment::where('parent_id', $commentId)
                ->with('user:id,name,profile_pic')
                ->orderBy('created_at', 'asc')
                ->get();

            return $this->successResponse([
                'parent_comment_id' => $commentId,
                'replies' => $replies,
                'total_replies' => $replies->count()
            ], 'Réponses récupérées avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des réponses', 500);
        }
    }
}