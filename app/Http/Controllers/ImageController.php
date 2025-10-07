<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
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
     * @OA\Post(
     *     path="/images/profile",
     *     tags={"Images"},
     *     summary="Upload d'image de profil",
     *     description="Télécharger et optimiser une image de profil. L'image est automatiquement redimensionnée en 300x300px et compressée.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier image (JPEG, PNG, WebP, max 5MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploadée et optimisée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="filename", type="string", example="1634567890_abcd1234.jpg"),
     *                 @OA\Property(property="url", type="string", example="/storage/profile-pictures/1634567890_abcd1234.jpg"),
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             ),
     *             @OA\Property(property="message", type="string", example="Photo de profil mise à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max pour tailleimage
            ]);

            $image = $request->file('image');
            $filename = $this->generateFilename($image);
            
          
            $processedImage = $this->processProfileImage($image);
            
            // Sauvegarder image
            $path = Storage::disk('public')->put('profile-pictures/' . $filename, $processedImage);
            
            // supprimerl'ancienne image si elle existe
            $user = $request->user();
            if ($user->profile_pic) {
                $this->removeImageFromStorage($user->profile_pic);
            }
            
            $user->update(['profile_pic' => $filename]);

            return $this->successResponse([
                'filename' => $filename,
                'url' => Storage::disk('public')->url('profile-pictures/' . $filename),
                'user' => $user
            ], 'Photo de profil mise à jour avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de l\'upload de l\'image', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/images/posts",
     *     tags={"Images"},
     *     summary="Upload d'images pour les posts",
     *     description="Télécharger jusqu'à 5 images pour un post (max 10MB chaque)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Tableau d'images (max 5, formats: jpeg,jpg,png,webp)",
     *                     maxItems=5
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images uploadées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="images", type="array", @OA\Items(
     *                     @OA\Property(property="filename", type="string", example="1634567890_abcd1234.jpg"),
     *                     @OA\Property(property="url", type="string", example="/storage/post-images/1634567890_abcd1234.jpg"),
     *                     @OA\Property(property="size", type="integer", example=45678)
     *                 )),
     *                 @OA\Property(property="count", type="integer", example=3)
     *             ),
     *             @OA\Property(property="message", type="string", example="Images uploadées avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function uploadPostImages(Request $request)
    {
        try {
            $validated = $request->validate([
                'images' => 'required|array|max:5', 
                'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:10240', 
            ]);

            $uploadedImages = [];
            
            foreach ($request->file('images') as $image) {
                $filename = $this->generateFilename($image);
                
                // Optimiser l'image
                $processedImage = $this->processPostImage($image);
                
                // Sauvegarder
                Storage::disk('public')->put('post-images/' . $filename, $processedImage);
                
                $uploadedImages[] = [
                    'filename' => $filename,
                    'url' => Storage::disk('public')->url('post-images/' . $filename),
                    'size' => strlen($processedImage)
                ];
            }

            return $this->successResponse([
                'images' => $uploadedImages,
                'count' => count($uploadedImages)
            ], 'Images uploadées avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de l\'upload des images', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/images",
     *     tags={"Images"},
     *     summary="Supprimer une image",
     *     description="Supprimer une image de profil ou de post du stockage",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"filename","type"},
     *             @OA\Property(property="filename", type="string", example="1634567890_abcd1234.jpg", description="Nom du fichier à supprimer"),
     *             @OA\Property(property="type", type="string", enum={"profile","post"}, example="post", description="Type d'image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image supprimée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Image supprimée avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Image non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function deleteImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'filename' => 'required|string',
                'type' => 'required|in:profile,post'
            ]);

            $directory = $validated['type'] === 'profile' ? 'profile-pictures' : 'post-images';
            $path = $directory . '/' . $validated['filename'];

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                // Si c'est une photo de profil, mettre à jour l'utilisateur
                if ($validated['type'] === 'profile') {
                    $request->user()->update(['profile_pic' => null]);
                }

                return $this->successResponse(null, 'Image supprimée avec succès');
            }

            return $this->errorResponse('Image non trouvée', 'Image non trouvée', 404);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la suppression', 500);
        }
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateFilename($image)
    {
        $extension = $image->getClientOriginalExtension();
        return time() . '_' . Str::random(20) . '.' . $extension;
    }

    /**
     * Traiter une image de profil (redimensionner et optimiser)
     */
    private function processProfileImage($image)
    {
        
        $processedImage = Image::make($image)
            ->fit(300, 300)
            ->encode('jpg', 85); // Compression JPEG à 85%

        return $processedImage->getEncoded();
    }

    /**
     * Traiter une image de post (optimiser sans redimensionner)
     */
    private function processPostImage($image)
    {
        $processedImage = Image::make($image);
        
        if ($processedImage->width() > 1920) {
            $processedImage->resize(1920, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $extension = strtolower($image->getClientOriginalExtension());
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $processedImage->encode('jpg', 85);
                break;
            case 'png':
                $processedImage->encode('png', 8); 
                break;
            case 'webp':
                $processedImage->encode('webp', 85);
                break;
            default:
                $processedImage->encode('jpg', 85);
        }

        return $processedImage->getEncoded();
    }

    /**
     * Supprimer une image du stockage (méthode privée)
     */
    private function removeImageFromStorage($filename)
    {
        if ($filename) {
            $paths = [
                'profile-pictures/' . $filename,
                'post-images/' . $filename
            ];
            
            foreach ($paths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    break;
                }
            }
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/images/cleanup",
     *     tags={"Admin", "Images"},
     *     summary="Nettoyer les images orphelines",
     *     description="Supprimer les images qui ne sont plus référencées par aucun utilisateur ou post (Admin uniquement)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nettoyage terminé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=12)
     *             ),
     *             @OA\Property(property="message", type="string", example="Nettoyage terminé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Admin requis",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     )
     * )
     */
    public function cleanupOrphanedImages()
    {
        try {
            $profileImages = Storage::disk('public')->files('profile-pictures');
            $postImages = Storage::disk('public')->files('post-images');
            
            $deletedCount = 0;
            
            // Nettoyer les images de profil
            $usedProfilePics = \App\Models\User::whereNotNull('profile_pic')
                ->pluck('profile_pic')
                ->toArray();
                
            foreach ($profileImages as $imagePath) {
                $filename = basename($imagePath);
                if (!in_array($filename, $usedProfilePics)) {
                    Storage::disk('public')->delete($imagePath);
                    $deletedCount++;
                }
            }

            return $this->successResponse([
                'deleted_count' => $deletedCount
            ], 'Nettoyage terminé');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors du nettoyage', 500);
        }
    }
}