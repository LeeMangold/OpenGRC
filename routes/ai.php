<?php

use App\Http\Controllers\MCP\OAuthRegisterController;
use App\Mcp\Servers\OpenGrcServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes (MCP Servers)
|--------------------------------------------------------------------------
|
| This file defines the MCP (Model Context Protocol) server endpoints.
| These routes allow AI clients to interact with OpenGRC via HTTP.
|
| OAuth Endpoints:
|   - /.well-known/oauth-protected-resource/{path?} - Resource metadata
|   - /.well-known/oauth-authorization-server/{path?} - OAuth server config
|   - /oauth/register - Dynamic client registration
|   - /oauth/authorize - Authorization endpoint (via Passport)
|   - /oauth/token - Token endpoint (via Passport)
|
| MCP Endpoint:
|   - POST /mcp/opengrc - MCP server (requires OAuth token with mcp:use scope)
|
*/

// OAuth 2.1 discovery and registration endpoints
// These are public routes for OAuth discovery, but MCP must be enabled
Route::middleware(['mcp.enabled'])->group(function () {
    Mcp::oauthRoutes('oauth');

    // Override the default oauth/register route with Passport v12 compatible controller
    Route::post('oauth/register', OAuthRegisterController::class);

    // Override the discovery endpoint to add token_endpoint_auth_methods_supported
    // This is required for MCP OAuth 2.1 public clients
    Route::get('/.well-known/oauth-authorization-server/{path?}', function (?string $path = '') {
        return response()->json([
            'issuer' => url('/'.$path),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'registration_endpoint' => url('oauth/register'),
            'token_endpoint_auth_methods_supported' => ['none'],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => ['mcp:use'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
        ]);
    })->where('path', '.*');
});

// HTTP MCP endpoint - requires OAuth token via Passport
// Enable/disable via Settings > AI Settings > MCP Server
Route::middleware(['mcp.enabled'])->group(function () {
    Mcp::web('/mcp/opengrc', OpenGrcServer::class)
        ->middleware(['auth:api', 'throttle:mcp']);
});
