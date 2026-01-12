# OpenGRC MCP Server

OpenGRC provides a Model Context Protocol (MCP) server over HTTP that allows AI clients (like Claude Code) to interact with OpenGRC programmatically.

## Features

The MCP server exposes **6 unified tools** that provide full CRUD access to all GRC entities:

### Entity Management (CRUD)
- **ListEntities**: List any entity type with filtering, search, and pagination
- **GetEntity**: Get detailed information about a specific entity by ID or code
- **CreateEntity**: Create a new entity of any supported type
- **UpdateEntity**: Update an existing entity
- **DeleteEntity**: Delete an entity (with confirmation required)

### Reference Data
- **GetTaxonomyValues**: Get valid values for policy status, scope, departments

### Supported Entity Types

All CRUD tools support these entity types via the `type` parameter:

| Type | Description | Has Code |
|------|-------------|----------|
| `standard` | Compliance frameworks (NIST, ISO, SOC2, etc.) | Yes |
| `control` | Security controls within standards | Yes |
| `implementation` | How controls are implemented | No |
| `policy` | Security and compliance policies | Yes (auto-generated) |
| `risk` | Risk register entries | No |
| `program` | Organizational security programs | No |
| `audit` | Assessment/audit records | No |
| `audit_item` | Individual audit questions/items | No |
| `vendor` | Third-party vendors | No |
| `application` | Applications/systems | No |
| `asset` | IT assets | No |

## Endpoint

```
POST /mcp/opengrc
```

The endpoint requires OAuth 2.1 authentication via Bearer token (Laravel Passport).

---

## Prerequisites

Before enabling the MCP server, you must have Passport encryption keys configured. These are required for OAuth token generation.

### Option 1: Generate Key Files (Recommended for Development)

```bash
php artisan passport:keys
```

This creates `storage/oauth-private.key` and `storage/oauth-public.key`.

### Option 2: Environment Variables (Recommended for Production)

Set the keys directly in your `.env` file:

```env
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
...your private key...
-----END RSA PRIVATE KEY-----"

PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...your public key...
-----END PUBLIC KEY-----"
```

**Important:** If Passport keys are not configured, you will not be able to enable the MCP server.

---

## Setup for Claude Code

### 1. Ensure Passport Keys Exist

Verify keys are configured:

```bash
# Check for key files
ls -la storage/oauth-*.key

# Or check if environment variables are set
php artisan tinker --execute="echo config('passport.private_key') ? 'Keys configured' : 'Keys missing';"
```

If missing, generate them:

```bash
php artisan passport:keys
```

### 2. Enable the MCP Server

1. Log in to OpenGRC as an administrator
2. Navigate to **Admin** > **Settings** > **AI Settings**
3. Toggle **Enable MCP Server** to on
4. Click **Save**

### 3. Start the Development Server

```bash
php artisan serve
```

This starts the server at `http://127.0.0.1:8000`

### 4. Configure Claude Code with OAuth

Claude Code supports OAuth 2.1 authentication natively. Add the following to your Claude Code MCP settings:

**For local development:**
```json
{
  "mcpServers": {
    "opengrc": {
      "type": "http",
      "url": "http://127.0.0.1:8000/mcp/opengrc"
    }
  }
}
```

**For remote/production:**
```json
{
  "mcpServers": {
    "opengrc": {
      "type": "http",
      "url": "https://your-opengrc-domain.com/mcp/opengrc"
    }
  }
}
```

When you first connect, Claude Code will:
1. Discover OAuth endpoints via `/.well-known/oauth-authorization-server`
2. Dynamically register as a client via `/oauth/register`
3. Redirect you to authorize the connection
4. Obtain access and refresh tokens automatically

### 5. Test the Connection

In Claude Code, try commands like:
- "List all policies in OpenGRC"
- "Show me the available compliance standards"
- "Create a Security Awareness Policy"
- "List all vendors"
- "Get details for audit ID 1"

---

## OAuth 2.1 Endpoints

The MCP server exposes these OAuth 2.1 endpoints:

| Endpoint | Description |
|----------|-------------|
| `GET /.well-known/oauth-authorization-server` | OAuth server metadata discovery |
| `GET /.well-known/oauth-protected-resource/{path?}` | Protected resource metadata |
| `POST /oauth/register` | Dynamic client registration |
| `GET /oauth/authorize` | Authorization endpoint |
| `POST /oauth/token` | Token endpoint |

### OAuth Scopes

| Scope | Description |
|-------|-------------|
| `mcp:use` | Access to the MCP server (required) |

### Token Expiration

| Token Type | Expiration |
|------------|------------|
| Access Token | 60 minutes |
| Refresh Token | 7 days |
| Personal Access Token | 6 months |

---

## Testing with cURL

### 1. Register an OAuth Client (Optional)

If you need to test manually without Claude's automatic registration:

```bash
curl -X POST http://127.0.0.1:8000/oauth/register \
  -H "Content-Type: application/json" \
  -d '{
    "redirect_uris": ["http://localhost:8080/callback"],
    "client_name": "Test MCP Client"
  }'
```

### 2. Test MCP Endpoint (with Bearer Token)

Once you have a token:

```bash
# Initialize the connection
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "initialize", "id": 1, "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test", "version": "1.0"}}}'

# List available tools
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "tools/list", "id": 2}'

# List policies
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "tools/call", "id": 3, "params": {"name": "ListEntities", "arguments": {"type": "policy"}}}'

# Get a specific standard
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "tools/call", "id": 4, "params": {"name": "GetEntity", "arguments": {"type": "standard", "id": 1}}}'
```

---

## Tool Reference

### ListEntities

Lists entities of any supported type with filtering and pagination.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Entity type (see supported types above) |
| search | string | No | Search term to filter by name, code, or description |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |
| filter | object | No | Filter by related IDs, e.g., `{"standard_id": 1}` |

**Example:**
```json
{
  "name": "ListEntities",
  "arguments": {
    "type": "control",
    "filter": {"standard_id": 1},
    "search": "access",
    "per_page": 50
  }
}
```

### GetEntity

Retrieves a specific entity by ID or code.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Entity type |
| id | integer | No* | Entity database ID |
| code | string | No* | Entity code (for types that have codes) |

*One of `id` or `code` is required.

**Example:**
```json
{
  "name": "GetEntity",
  "arguments": {
    "type": "policy",
    "code": "POL-001"
  }
}
```

### CreateEntity

Creates a new entity.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Entity type |
| data | object | Yes | Entity data (fields vary by type) |

**Example - Creating a Policy:**
```json
{
  "name": "CreateEntity",
  "arguments": {
    "type": "policy",
    "data": {
      "name": "Security Awareness Policy",
      "purpose": "<p>Establish guidelines for security training.</p>",
      "policy_scope": "<p>All employees and contractors.</p>",
      "body": "<h2>1.0 Training</h2><p>Annual security training required.</p>",
      "effective_date": "2025-01-01"
    }
  }
}
```

**Example - Creating a Risk:**
```json
{
  "name": "CreateEntity",
  "arguments": {
    "type": "risk",
    "data": {
      "name": "Data Breach Risk",
      "likelihood": 3,
      "impact": 5
    }
  }
}
```

### UpdateEntity

Updates an existing entity.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Entity type |
| id | integer | Yes | Entity database ID |
| data | object | Yes | Fields to update |

**Example:**
```json
{
  "name": "UpdateEntity",
  "arguments": {
    "type": "policy",
    "id": 1,
    "data": {
      "name": "Updated Policy Name",
      "effective_date": "2025-06-01"
    }
  }
}
```

### DeleteEntity

Deletes an entity (requires confirmation).

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Entity type |
| id | integer | Yes | Entity database ID |
| confirm | boolean | Yes | Must be `true` to confirm deletion |

**Example:**
```json
{
  "name": "DeleteEntity",
  "arguments": {
    "type": "risk",
    "id": 5,
    "confirm": true
  }
}
```

### GetTaxonomyValues

Gets available taxonomy values for entity fields.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | Taxonomy type (e.g., "policy-status", "policy-scope", "department") |

**Example:**
```json
{
  "name": "GetTaxonomyValues",
  "arguments": {
    "type": "policy-status"
  }
}
```

---

## Common Workflows

### Creating a Complete Policy

1. Check existing policies:
   ```json
   {"name": "ListEntities", "arguments": {"type": "policy"}}
   ```

2. Get valid status values:
   ```json
   {"name": "GetTaxonomyValues", "arguments": {"type": "policy-status"}}
   ```

3. Find controls to link:
   ```json
   {"name": "ListEntities", "arguments": {"type": "control", "search": "access"}}
   ```

4. Create the policy:
   ```json
   {
     "name": "CreateEntity",
     "arguments": {
       "type": "policy",
       "data": {
         "name": "Access Control Policy",
         "purpose": "<p>Define access control requirements.</p>",
         "body": "<h2>1.0 Requirements</h2><ul><li>MFA required</li></ul>"
       }
     }
   }
   ```

### Reviewing Compliance Posture

1. List all standards:
   ```json
   {"name": "ListEntities", "arguments": {"type": "standard"}}
   ```

2. Get controls for a standard:
   ```json
   {"name": "ListEntities", "arguments": {"type": "control", "filter": {"standard_id": 1}}}
   ```

3. Check implementation status:
   ```json
   {"name": "GetEntity", "arguments": {"type": "control", "id": 42}}
   ```

---

## Production Deployment

For production use:

1. **Passport Keys**: Set `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` environment variables instead of using key files
2. **HTTPS**: Ensure HTTPS is enabled (required for OAuth 2.1)
3. **Redirect Domains**: Configure `mcp.redirect_domains` in config to restrict allowed OAuth redirect URIs
4. **Rate Limiting**: The MCP endpoint is rate-limited to 120 requests per minute

---

## Troubleshooting

### MCP Server Cannot Be Enabled

**Passport keys not configured:**
- Run `php artisan passport:keys` to generate key files
- Or set `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` in your `.env`

### 401 Unauthorized
- Your OAuth access token may have expired (tokens expire after 60 minutes)
- The client should automatically refresh using the refresh token
- Ensure the `Authorization` header is correctly formatted as `Bearer YOUR_TOKEN`

### 503 Service Unavailable
- The MCP server is disabled
- Enable it in Admin > Settings > AI Settings
- If you can't enable it, check that Passport keys are configured

### 404 Not Found
- Verify the server is running and accessible
- Check that routes are registered: `php artisan route:list --path=mcp`

### 429 Too Many Requests
- The MCP endpoint is rate-limited to 120 requests per minute
- Wait a moment and try again

### Invalid Entity Type
- Check that the `type` parameter matches one of the supported types
- Entity types are case-sensitive and use snake_case (e.g., `audit_item`)

### OAuth Client Registration Fails
- Check that the redirect URI is a valid URL
- Verify the domain is in the allowed list (see `config/mcp.php` `redirect_domains`)

### CORS Issues (Browser-based clients)
- Configure CORS in `config/cors.php` to allow your client domain
