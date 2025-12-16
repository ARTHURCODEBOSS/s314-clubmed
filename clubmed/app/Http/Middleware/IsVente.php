<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsVente
{
    public function handle(Request $request, Closure $next): Response
    {
        
        if ($request->user() && strtolower($request->user()->role) === 'vente') {
            return $next($request);
        }

        return response()->json(['message' => 'Accès interdit. Réservé au service vente.'], 403);
    }
}