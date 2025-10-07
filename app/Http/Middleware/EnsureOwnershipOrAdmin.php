<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOwnershipOrAdmin
{
    public function handle(Request $request, Closure $next, $resourceType = null)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'error' => 'Non authentifié',
                'data' => null,
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $resourceId = $this->extractResourceId($request, $resourceType);
        
        if ($resourceId && !$this->isOwner($user, $resourceType, $resourceId)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Accès refusé',
                'data' => null,
                'message' => 'Vous ne pouvez modifier que vos propres ressources'
            ], 403);
        }

        return $next($request);
    }

    private function extractResourceId($request, $resourceType)
    {
        switch ($resourceType) {
            case 'post':
                return $request->route('post');
            case 'comment':
                return $request->route('comment');
            case 'rating':
                return $request->route('rating');
            default:
                return null;
        }
    }

    private function isOwner($user, $resourceType, $resourceId)
    {
        switch ($resourceType) {
            case 'post':
                return \App\Models\Post::where('id', $resourceId)
                    ->where('user_id', $user->id)
                    ->exists();
            case 'comment':
                return \App\Models\Comment::where('id', $resourceId)
                    ->where('user_id', $user->id)
                    ->exists();
            case 'rating':
                return \App\Models\Rating::where('id', $resourceId)
                    ->where('user_id', $user->id)
                    ->exists();
            default:
                return false;
        }
    }
}