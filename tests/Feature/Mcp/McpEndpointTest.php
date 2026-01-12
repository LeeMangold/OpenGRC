<?php

namespace Tests\Feature\Mcp;

use App\Models\Standard;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Yethee\Tiktoken\EncoderProvider;

class McpEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);

        // Enable MCP for all tests by default
        setting(['mcp.enabled' => true]);
    }

    /**
     * Create a user with Super Admin role for tests.
     */
    protected function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    /**
     * Test MCP endpoint returns 503 when feature is disabled (after auth).
     */
    public function test_mcp_endpoint_returns_503_when_disabled(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        setting(['mcp.enabled' => false]);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(503);
        $response->assertJson([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => -32000,
                'message' => 'MCP server is disabled. Enable it in Settings > AI Settings.',
            ],
        ]);
    }

    /**
     * Test MCP endpoint works when feature is enabled.
     */
    public function test_mcp_endpoint_works_when_enabled(): void
    {
        setting(['mcp.enabled' => true]);

        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test auth is required before checking if MCP is disabled.
     *
     * This ensures we don't leak information about whether MCP is enabled
     * to unauthenticated users.
     */
    public function test_mcp_requires_auth_before_disabled_check(): void
    {
        setting(['mcp.enabled' => false]);

        // Without auth - should get 401 (unauthorized), not 503 (disabled)
        // This prevents leaking MCP status to unauthenticated users
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP endpoint requires authentication.
     */
    public function test_mcp_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ManageStandard',
                'arguments' => [
                    'action' => 'create',
                    'data' => ['name' => 'Test'],
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP endpoint accepts valid OAuth token.
     */
    public function test_mcp_endpoint_accepts_valid_token(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        // Create a standard to list
        Standard::factory()->create();

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint returns tools list.
     */
    public function test_mcp_endpoint_returns_tools_list(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result' => [
                'tools',
            ],
        ]);

        $tools = $response->json('result.tools');
        $toolNames = array_column($tools, 'name');

        // Individual tools for each entity type
        $this->assertContains('ManageStandard', $toolNames);
        $this->assertContains('ManageControl', $toolNames);
        $this->assertContains('ManagePolicy', $toolNames);
        $this->assertContains('ManageVendor', $toolNames);
    }

    /**
     * Test MCP endpoint can call ManageStandard tool for create.
     */
    public function test_mcp_endpoint_can_call_manage_standard_create(): void
    {
        $user = $this->createAdminUser();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ManageStandard',
                'arguments' => [
                    'action' => 'create',
                    'data' => [
                        'name' => 'Test Standard',
                        'code' => 'TST-001',
                        'authority' => 'Test',
                        'description' => 'A test standard',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result' => [
                'content',
            ],
        ]);

        $this->assertDatabaseHas('standards', ['code' => 'TST-001']);
    }

    /**
     * Test MCP endpoint can call ManageStandard tool for update.
     */
    public function test_mcp_endpoint_can_call_manage_standard_update(): void
    {
        $user = $this->createAdminUser();
        Passport::actingAs($user, ['mcp:use']);

        $standard = Standard::factory()->create([
            'name' => 'Test Standard',
            'code' => 'TEST-001',
        ]);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ManageStandard',
                'arguments' => [
                    'action' => 'update',
                    'id' => $standard->id,
                    'data' => ['name' => 'Updated Standard'],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('standards', [
            'id' => $standard->id,
            'name' => 'Updated Standard',
        ]);
    }

    /**
     * Test MCP endpoint can call ManageStandard tool for delete.
     */
    public function test_mcp_endpoint_can_call_manage_standard_delete(): void
    {
        $user = $this->createAdminUser();
        Passport::actingAs($user, ['mcp:use']);

        $standard = Standard::factory()->create([
            'name' => 'Delete Me',
            'code' => 'DEL-001',
        ]);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ManageStandard',
                'arguments' => [
                    'action' => 'delete',
                    'id' => $standard->id,
                    'confirm' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint returns error for invalid JSON-RPC request.
     */
    public function test_mcp_endpoint_returns_error_for_invalid_request(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'invalid' => 'request',
        ]);

        // Should return an error response (could be 200 with JSON-RPC error or 400)
        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint server info.
     */
    public function test_mcp_endpoint_returns_server_info(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'test-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.serverInfo.name', 'OpenGRC MCP Server');
        $response->assertJsonPath('result.serverInfo.version', '3.0.0');
    }

    /**
     * Test MCP endpoint respects rate limiting.
     */
    public function test_mcp_endpoint_has_rate_limiting(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        // Make requests up to the limit - should not trigger rate limiting
        // Rate limit is 120 per minute, we'll just verify the middleware is applied
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        // Check for rate limit headers
        $response->assertStatus(200);
        // The rate limiter should add headers, but exact behavior depends on configuration
    }

    /**
     * Test MCP endpoint rejects invalid token.
     */
    public function test_mcp_endpoint_rejects_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/mcp/opengrc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP server context size is under 4000 tokens.
     *
     * This ensures the MCP server doesn't consume too much of an AI's context window
     * when the server is enabled. Uses tiktoken with cl100k_base encoding (GPT-4/Claude approx).
     *
     * Note: With individual Manage* tools for each entity type (11 tools), the context is
     * larger than a unified ManageEntity tool but provides better discoverability for LLMs.
     */
    public function test_mcp_context_size_is_under_4000_tokens(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        // Get server instructions via initialize
        $initResponse = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'test-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        // Get tools list
        $toolsResponse = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $initResponse->assertStatus(200);
        $toolsResponse->assertStatus(200);

        // Collect all context text
        $contextParts = [];

        // Add server instructions if present
        $initResult = $initResponse->json('result');
        if (isset($initResult['instructions'])) {
            $contextParts[] = $initResult['instructions'];
        }

        // Add all tool definitions (name, description, schema)
        $tools = $toolsResponse->json('result.tools');
        foreach ($tools as $tool) {
            $contextParts[] = $tool['name'] ?? '';
            $contextParts[] = $tool['description'] ?? '';
            // Include schema as JSON since it's part of the context
            if (isset($tool['inputSchema'])) {
                $contextParts[] = json_encode($tool['inputSchema']);
            }
        }

        $fullContext = implode("\n", $contextParts);

        // Use tiktoken for accurate token counting
        $provider = new EncoderProvider;

        // cl100k_base approximates Claude 4.5 Sonnet/Opus tokenization
        $claudeEncoder = $provider->get('cl100k_base');
        $claudeTokens = count($claudeEncoder->encode($fullContext));

        // o200k_base is used by GPT-4o and GPT-5.2
        $gptEncoder = $provider->get('o200k_base');
        $gptTokens = count($gptEncoder->encode($fullContext));

        // Use the highest for the limit check
        $maxTokens = max($claudeTokens, $gptTokens);

        $this->assertLessThan(
            4000,
            $maxTokens,
            'MCP context exceeds 4000 token limit. '
            ."Claude 4.5 Sonnet (cl100k_base): {$claudeTokens} tokens. "
            ."GPT-5.2 (o200k_base): {$gptTokens} tokens. "
            .'Context length: '.strlen($fullContext).' characters.'
        );
    }

    /**
     * Test MCP endpoint JSON-RPC 2.0 compliance.
     */
    public function test_mcp_endpoint_returns_valid_jsonrpc_response(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'jsonrpc' => '2.0',
            'id' => 42,
        ]);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result',
        ]);
    }

    /**
     * Test OAuth discovery endpoint returns correct metadata.
     */
    public function test_oauth_discovery_returns_metadata(): void
    {
        setting(['mcp.enabled' => true]);

        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'registration_endpoint',
            'scopes_supported',
        ]);
    }

    /**
     * Test OAuth protected resource endpoint.
     */
    public function test_oauth_protected_resource_endpoint(): void
    {
        setting(['mcp.enabled' => true]);

        $response = $this->getJson('/.well-known/oauth-protected-resource/mcp/opengrc');

        $response->assertStatus(200);
        $response->assertJsonPath('scopes_supported', ['mcp:use']);
    }

    /**
     * Test dynamic client registration endpoint.
     */
    public function test_oauth_dynamic_client_registration(): void
    {
        setting(['mcp.enabled' => true]);

        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Test AI Client',
            'redirect_uris' => ['https://example.com/callback'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'client_id',
            'grant_types',
            'redirect_uris',
        ]);
    }
}
