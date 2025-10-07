<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
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
     *     path="/posts/{post}/report",
     *     tags={"Reports"},
     *     summary="Signaler un post",
     *     description="Signaler un post pour contenu inapproprié. Déclenche des actions automatiques selon le nombre de signalements (3 = avertissement, 5 = désactivation).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         description="ID du post à signaler",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", enum={"spam","inappropriate","harassment","fake","other"}, example="spam", description="Raison du signalement"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Ce post contient du spam publicitaire", description="Description détaillée (optionnel, max 500 caractères)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Signalement créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="report", ref="#/components/schemas/Report"),
     *                 @OA\Property(property="total_reports", type="integer", example=2)
     *             ),
     *             @OA\Property(property="message", type="string", example="Post signalé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Déjà signalé ou erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="error", type="string", example="Vous avez déjà signalé ce post"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Signalement déjà effectué")
     *         )
     *     )
     * )
     */
    public function reportPost(Request $request, $postId)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|in:spam,inappropriate,harassment,fake,other',
                'description' => 'nullable|string|max:500'
            ]);

            $post = Post::findOrFail($postId);
            $userId = $request->user()->id;

            
            $existingReport = Report::where('post_id', $postId)
                ->where('reporter_user_id', $userId)
                ->first();

            if ($existingReport) {
                return $this->errorResponse('Vous avez déjà signalé ce post', 'Signalement déjà effectué', 422);
            }

            // Créer le signalement
            $report = Report::create([
                'post_id' => $postId,
                'reporter_user_id' => $userId,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'status' => Report::STATUS_PENDING,
            ]);

            
            $totalReports = Report::where('post_id', $postId)->count();

            $this->handleReportThresholds($post, $totalReports);

            return $this->successResponse([
                'report' => $report,
                'total_reports' => $totalReports
            ], 'Post signalé avec succès', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors du signalement', 500);
        }
    }

    /**
     * Gérer les seuils de signalement (3 et 5)
     */
    private function handleReportThresholds($post, $totalReports)
    {
        $postOwner = $post->user;

        if ($totalReports == 3) {
            $this->sendWarningEmail($postOwner, $post, $totalReports);
        } elseif ($totalReports == 5) {
            $this->sendWarningEmail($postOwner, $post, $totalReports);
            $this->deactivateUser($postOwner);
        }
    }

    /**
     * Envoyer un email d'avertissement
     */
    private function sendWarningEmail($user, $post, $reportCount)
    {
        $mailService = new \App\Services\MailService();
        $mailService->sendReportWarning($user, $post, $reportCount);
    }

    /**
     * Désactiver un utilisateur
     */
    private function deactivateUser($user)
    {
        $user->update(['is_active' => false]);
        \Log::info("Utilisateur {$user->id} désactivé pour trop de signalements");
    }

    /**
     * @OA\Get(
     *     path="/posts/{post}/reports",
     *     tags={"Reports"},
     *     summary="Lister les signalements d'un post",
     *     description="Récupérer tous les signalements d'un post avec statistiques par raison",
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
     *         description="Signalements récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="post_id", type="integer", example=1),
     *                 @OA\Property(property="reports", type="array", @OA\Items(ref="#/components/schemas/Report")),
     *                 @OA\Property(property="total_reports", type="integer", example=3),
     *                 @OA\Property(property="reports_by_reason", type="object",
     *                     @OA\Property(property="spam", type="integer", example=1),
     *                     @OA\Property(property="inappropriate", type="integer", example=1),
     *                     @OA\Property(property="harassment", type="integer", example=0),
     *                     @OA\Property(property="fake", type="integer", example=1),
     *                     @OA\Property(property="other", type="integer", example=0)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Signalements récupérés avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     )
     * )
     */
    public function getPostReports($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            
            $reports = Report::where('post_id', $postId)
                ->with('reporter:id,name,email')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse([
                'post_id' => $postId,
                'reports' => $reports,
                'total_reports' => $reports->count(),
                'reports_by_reason' => [
                    'spam' => $reports->where('reason', 'spam')->count(),
                    'inappropriate' => $reports->where('reason', 'inappropriate')->count(),
                    'harassment' => $reports->where('reason', 'harassment')->count(),
                    'fake' => $reports->where('reason', 'fake')->count(),
                    'other' => $reports->where('reason', 'other')->count(),
                ]
            ], 'Signalements récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors de la récupération des signalements', 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/admin/reports/{report}",
     *     tags={"Admin"},
     *     summary="Traiter un signalement",
     *     description="Marquer un signalement comme traité avec une note administrative (Admin uniquement)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="ID du signalement",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"reviewed","resolved","rejected"}, example="resolved", description="Nouveau statut du signalement"),
     *             @OA\Property(property="admin_note", type="string", nullable=true, example="Contenu supprimé après vérification", description="Note administrative (optionnel, max 500 caractères)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signalement traité avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="error", type="null"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="report", ref="#/components/schemas/Report")
     *             ),
     *             @OA\Property(property="message", type="string", example="Signalement traité avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Signalement non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function resolveReport(Request $request, $reportId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:reviewed,resolved,rejected',
                'admin_note' => 'nullable|string|max:500'
            ]);

            $report = Report::findOrFail($reportId);
            
            $report->update([
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'resolved_at' => now(),
                'resolved_by' => $request->user()->id
            ]);

            return $this->successResponse([
                'report' => $report
            ], 'Signalement traité avec succès');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 'Erreur de validation', 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 'Erreur lors du traitement du signalement', 500);
        }
    }
}