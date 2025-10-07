<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/posts",
     *     tags={"Posts"},
     *     summary="Lister tous les posts",
     *     description="Récupérer tous les posts de la communauté avec les informations utilisateur",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des posts récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post")),
     *             @OA\Property(property="message", type="string", example="List of posts retrieved")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $posts = Post::all();
        return response()->json([
            'status' => 200,
            'error' => null,
            'data' => $posts,
            'message' => 'List of posts retrieved',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/posts/{id}",
     *     tags={"Posts"},
     *     summary="Afficher un post spécifique",
     *     description="Récupérer les détails d'un post par son ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du post",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", ref="#/components/schemas/Post"),
     *             @OA\Property(property="message", type="string", example="Post found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="Post not found"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="The requested post does not exist")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $post = Post::findOrFail($id);
            return response()->json([
                'status' => 200,
                'error' => null,
                'data' => $post,
                'message' => 'Post found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'error' => 'Post not found',
                'data' => (object)[],
                'message' => 'The requested post does not exist',
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/posts",
     *     tags={"Posts"},
     *     summary="Créer un nouveau post",
     *     description="Créer un nouveau post dans la communauté",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","title","content"},
     *             @OA\Property(property="user_id", type="integer", example=1, description="ID de l'utilisateur créateur"),
     *             @OA\Property(property="title", type="string", example="Mon projet Arduino", description="Titre du post"),
     *             @OA\Property(property="content", type="string", example="Description détaillée de mon projet...", description="Contenu du post"),
     *             @OA\Property(property="link", type="string", nullable=true, example="https://github.com/user/projet", description="Lien externe optionnel"),
     *             @OA\Property(property="photos", type="string", nullable=true, example="image1.jpg,image2.jpg", description="Liste des photos (noms séparés par virgules)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", ref="#/components/schemas/Post"),
     *             @OA\Property(property="message", type="string", example="Post created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'title' => 'required|string',
                'content' => 'required|string',
                'link' => 'nullable|string',
                'photos' => 'nullable|json',
                'category_id' => 'required|exists:categories,id',
            ]);
            $post = Post::create($validated);
            return response()->json([
                'status' => 201,
                'error' => null,
                'data' => $post,
                'message' => 'Post created successfully',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 400,
                'error' => $e->getMessage(),
                'data' => (object)[],
                'message' => 'Validation error',
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/posts/{id}",
     *     tags={"Posts"},
     *     summary="Mettre à jour un post",
     *     description="Modifier les informations d'un post existant",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du post à modifier",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","title","content"},
     *             @OA\Property(property="user_id", type="integer", example=1, description="ID de l'utilisateur"),
     *             @OA\Property(property="title", type="string", example="Titre modifié", description="Titre du post"),
     *             @OA\Property(property="content", type="string", example="Contenu modifié du post", description="Contenu du post"),
     *             @OA\Property(property="link", type="string", nullable=true, example="https://exemple.com", description="Lien optionnel"),
     *             @OA\Property(property="photos", type="string", nullable=true, example="photo1.jpg,photo2.jpg", description="Photos au format JSON")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", ref="#/components/schemas/Post"),
     *             @OA\Property(property="message", type="string", example="Post updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="Post not found"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="The post to update does not exist")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'title' => 'required|string',
                'content' => 'required|string',
                'link' => 'nullable|string',
                'photos' => 'nullable|json',
            ]);
            $post->update($validated);
            return response()->json([
                'status' => 200,
                'error' => null,
                'data' => $post,
                'message' => 'Post updated successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 400,
                'error' => $e->getMessage(),
                'data' => (object)[],
                'message' => 'Validation error',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'error' => 'Post not found',
                'data' => (object)[],
                'message' => 'The post to update does not exist',
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/posts/{id}",
     *     tags={"Posts"},
     *     summary="Supprimer un post",
     *     description="Supprimer définitivement un post",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du post à supprimer",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Post deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="Post not found"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="The post to delete does not exist")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->delete();
            return response()->json([
                'status' => 200,
                'error' => null,
                'data' => (object)[],
                'message' => 'Post deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'error' => 'Post not found',
                'data' => (object)[],
                'message' => 'The post to delete does not exist',
            ], 404);
        }
    }
}
