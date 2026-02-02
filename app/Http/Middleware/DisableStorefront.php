<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableStorefront
{
    public function handle(Request $request, Closure $next): Response
    {
        return response()->json([
            'message' => 'Storefront disabled. Use API endpoints.',
        ], Response::HTTP_NOT_FOUND);
    }
}
