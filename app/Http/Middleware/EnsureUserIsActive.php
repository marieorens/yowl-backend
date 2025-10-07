<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
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

        if (!$user->isActive()) {
            return response()->json([
                'status' => 'error',
                'error' => 'Compte désactivé',
                'data' => null,
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        return $next($request);
    }
}