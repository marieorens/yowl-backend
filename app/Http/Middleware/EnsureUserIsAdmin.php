<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'error' => 'Accès refusé',
                'data' => null,
                'message' => 'Vous devez être administrateur pour accéder à cette ressource'
            ], 403);
        }

        return $next($request);
    }
}