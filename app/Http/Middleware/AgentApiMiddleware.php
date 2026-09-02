<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('security.agent_api_key');

        if (!$expectedKey) {
            return response()->json([
                'success' => false,
                'message' => 'AGENT_API_KEY not configured on server',
            ], 500);
        }

        $providedKey = $request->header('X-Agent-Key');

        if (!$providedKey || !hash_equals($expectedKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - invalid agent API key',
            ], 401);
        }

        return $next($request);
    }
}
