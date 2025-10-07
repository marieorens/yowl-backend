<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkPreviewController;



// Routes publiques d'authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes de vérification email et reset password (publiques)
Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);
Route::post('/password/reset/request', [AuthController::class, 'requestPasswordReset']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// Routes publiques
Route::get('/users/{user}/profile', [AuthController::class, 'showUserProfile']);

// Routes authentifiées
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    
    // Images
    Route::post('/images/profile', [ImageController::class, 'uploadProfilePicture']);
    Route::post('/images/posts', [ImageController::class, 'uploadPostImages']);
    Route::delete('/images', [ImageController::class, 'deleteImage']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'API fonctionne !']);
});

Route::get('/preview-link', [LinkPreviewController::class, 'getPreview']);


Route::apiResource('posts', PostController::class);
Route::apiResource('categories', CategoryController::class);


Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::get('/posts/{post}/comments/{comment}', [CommentController::class, 'show']);
    Route::put('/posts/{post}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/posts/{post}/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/posts/{post}/comments/{comment}/replies', [CommentController::class, 'replies']);
    
    Route::get('/posts/{post}/reactions', [ReactionController::class, 'getStats']);
    Route::post('/posts/{post}/reactions', [ReactionController::class, 'store']);
    Route::delete('/posts/{post}/reactions', [ReactionController::class, 'destroy']);
    Route::get('/posts/{post}/reactions/user/me', [ReactionController::class, 'getUserReaction']);
    
    // Signalements
    Route::post('/posts/{post}/report', [ReportController::class, 'reportPost']);
    Route::get('/posts/{post}/reports', [ReportController::class, 'getPostReports']);
});

// Routes admin uniquement
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::patch('/users/{user}/status', [AdminController::class, 'toggleUserStatus']);
    Route::get('/posts', [AdminController::class, 'posts']);
    Route::delete('/posts/{post}', [AdminController::class, 'deletePost']);
    Route::get('/reports', [AdminController::class, 'reports']);
    Route::patch('/reports/{report}', [ReportController::class, 'resolveReport']);
    Route::get('/statistics', [AdminController::class, 'statistics']);
    Route::post('/images/cleanup', [ImageController::class, 'cleanupOrphanedImages']);
});

