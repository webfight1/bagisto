<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableStorefront
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow GraphQL and GraphiQL endpoints
        $allowedPaths = [
            'graphql',
            'graphiql',
            'api/docs',
            'api/v1/*',
            'shop/api/*',
            'customer/reset-password/*',
            'customer/forgot-password',
            'customer/login',
            'customer/register',
        ];
        
        foreach ($allowedPaths as $path) {
            if ($request->is($path) || $request->is($path . '*')) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Storefront disabled. Use API endpoints.',
        ], Response::HTTP_NOT_FOUND);
    }
}
