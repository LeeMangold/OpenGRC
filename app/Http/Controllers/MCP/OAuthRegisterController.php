<?php

namespace App\Http\Controllers\MCP;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

/**
 * Custom OAuth client registration controller for MCP.
 *
 * This overrides the laravel/mcp package's controller to work with
 * Laravel Passport v12's API (which uses `create()` instead of
 * `createAuthorizationCodeGrantClient()`).
 */
class OAuthRegisterController
{
    public function __construct(
        protected ClientRepository $clients
    ) {}

    /**
     * Register a new OAuth client for a third-party application.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', function (string $attribute, $value, $fail): void {
                if (in_array('*', config('mcp.redirect_domains', []), true)) {
                    return;
                }

                if (! Str::startsWith($value, $this->allowedDomains())) {
                    $fail($attribute.' is not a permitted redirect domain.');
                }
            }],
        ]);

        // Create an authorization code grant client using Passport v12 API
        // Parameters: userId, name, redirect, provider, personalAccess, password, confidential
        $client = $this->clients->create(
            userId: null,
            name: $request->get('client_name', $request->get('name', 'MCP Client')),
            redirect: implode(',', $validated['redirect_uris']),
            provider: null,
            personalAccess: false,
            password: false,
            confidential: false, // Public client for MCP
        );

        return response()->json([
            'client_id' => (string) $client->id,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'redirect_uris' => $validated['redirect_uris'],
            'scope' => 'mcp:use',
            'token_endpoint_auth_method' => 'none',
        ]);
    }

    /**
     * Get the allowed redirect domains.
     *
     * @return array<int, string>
     */
    protected function allowedDomains(): array
    {
        /** @var array<int, string> */
        $allowedDomains = config('mcp.redirect_domains', []);

        return collect($allowedDomains)
            ->map(fn (string $domain): string => Str::endsWith($domain, '/')
                ? $domain
                : "{$domain}/"
            )
            ->all();
    }
}
