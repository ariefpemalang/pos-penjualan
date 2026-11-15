<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Lewati pengecekan untuk preflight CORS request (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $expected = (string) config('api.key');
        $provided = (string) $request->header('X-API-Key', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid atau tidak dikirim.',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
