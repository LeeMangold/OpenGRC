<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpEnabled
{
    /**
     * Handle an incoming request.
     *
     * Check if the MCP server feature is enabled. If disabled, return a
     * 503 Service Unavailable response with a JSON-RPC error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mcpEnabled = setting('mcp.enabled');

        if ($mcpEnabled !== true && $mcpEnabled !== 'true') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $request->input('id'),
                'error' => [
                    'code' => -32000,
                    'message' => 'MCP server is disabled. Enable it in Settings > AI Settings.',
                ],
            ], 503);
        }

        return $next($request);
    }
}
