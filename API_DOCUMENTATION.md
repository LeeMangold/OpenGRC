# OpenGRC API Documentation

## Overview

The OpenGRC API provides RESTful endpoints for managing all resources in the GRC system. All endpoints require authentication via Laravel Sanctum tokens and respect the permission system based on user roles.

## Authentication

### Generate API Token

Users can generate API tokens from the Filament admin panel using the built-in Sanctum token management (Filament Breezy).

1. Log in to the OpenGRC web interface
2. Navigate to your profile settings
3. Generate a new API token
4. Copy the token (it will only be shown once)

### Using the Token

Include the token in all API requests using the `Authorization` header:

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

## Rate Limiting

API requests are rate-limited to **60 requests per minute** per user (or IP address for unauthenticated requests).

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `Retry-After`: Seconds to wait before retrying (when rate limited)

## Permissions

All endpoints use Laravel policies for authorization, which check permissions via the Spatie Permission system. The API uses the same policies as the Filament web interface, ensuring consistent authorization across both.

Required permissions follow this pattern:

- `GET /api/resources` → `List {Resources}` permission (via `viewAny` policy)
- `POST /api/resources` → `Create {Resources}` permission (via `create` policy)
- `GET /api/resources/{id}` → `Read {Resources}` permission (via `view` policy)
- `PUT/PATCH /api/resources/{id}` → `Update {Resources}` permission (via `update` policy)
- `DELETE /api/resources/{id}` → `Delete {Resources}` permission (via `delete` policy)
- `POST /api/resources/{id}/restore` → `Update {Resources}` permission (via `restore` policy)

If a user lacks the required permission, the API returns a `403 Forbidden` response.

## Common Query Parameters

### Pagination

All index endpoints support pagination:

- `per_page` - Number of results per page (default: 15, max: 100)
- `page` - Page number (default: 1)
- `no_pagination` - Set to `true` to disable pagination and return all results

Example:
```bash
GET /api/standards?per_page=25&page=2
```

### Searching

Search across searchable fields:

- `search` - Search term to filter results

Example:
```bash
GET /api/controls?search=encryption
```

### Sorting

Sort results by any sortable field:

- `sort` - Field name to sort by
- `direction` - Sort direction (`asc` or `desc`)

Example:
```bash
GET /api/audits?sort=created_at&direction=desc
```

### Eager Loading

Load relationships using the `with` parameter:

```bash
GET /api/controls/1?with=standard,implementations
```

## Available Endpoints

### Users

**Base URL:** `/api/users`

- `GET /api/users` - List all users
- `POST /api/users` - Create a new user
- `GET /api/users/{id}` - Get a specific user
- `PUT /api/users/{id}` - Update a user
- `DELETE /api/users/{id}` - Delete a user (soft delete)
- `POST /api/users/{id}/restore` - Restore a soft-deleted user

**Searchable Fields:** `name`, `email`

**Relations:** `roles`, `permissions`, `managedPrograms`

**Required Permissions:**
- Only users with "Manage Users" permission can access these endpoints
- Password must meet security requirements: minimum 12 characters, mixed case, not previously compromised

**Create User Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "roles": ["Regular User"]
}
```

**Update User Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "roles": ["Security Admin", "Internal Auditor"]
}
```

**Notes:**
- Passwords are hashed automatically using Laravel's secure hashing
- Password confirmation is required when creating or updating passwords
- Roles can be assigned/updated via the `roles` array
- Sensitive fields (password, remember_token) are hidden in responses
- Users are soft-deleted by default

### Standards

**Base URL:** `/api/standards`

- `GET /api/standards` - List all standards
- `POST /api/standards` - Create a new standard
- `GET /api/standards/{id}` - Get a specific standard
- `PUT /api/standards/{id}` - Update a standard
- `DELETE /api/standards/{id}` - Delete a standard
- `POST /api/standards/{id}/restore` - Restore a soft-deleted standard

**Searchable Fields:** `code`, `title`, `description`

**Relations:** `controls`, `programs`

### Controls

**Base URL:** `/api/controls`

- `GET /api/controls` - List all controls
- `POST /api/controls` - Create a new control
- `GET /api/controls/{id}` - Get a specific control
- `PUT /api/controls/{id}` - Update a control
- `DELETE /api/controls/{id}` - Delete a control
- `POST /api/controls/{id}/restore` - Restore a soft-deleted control

**Searchable Fields:** `identifier`, `title`, `description`, `standard.code`, `standard.title`

**Relations:** `standard`, `implementations`, `controlOwner`

### Implementations

**Base URL:** `/api/implementations`

- `GET /api/implementations` - List all implementations
- `POST /api/implementations` - Create a new implementation
- `GET /api/implementations/{id}` - Get a specific implementation
- `PUT /api/implementations/{id}` - Update an implementation
- `DELETE /api/implementations/{id}` - Delete an implementation
- `POST /api/implementations/{id}/restore` - Restore a soft-deleted implementation

**Searchable Fields:** `title`, `details`, `notes`

**Relations:** `controls`, `risks`, `assets`, `implementationOwner`

### Audits

**Base URL:** `/api/audits`

- `GET /api/audits` - List all audits
- `POST /api/audits` - Create a new audit
- `GET /api/audits/{id}` - Get a specific audit
- `PUT /api/audits/{id}` - Update an audit
- `DELETE /api/audits/{id}` - Delete an audit
- `POST /api/audits/{id}/restore` - Restore a soft-deleted audit

**Searchable Fields:** `title`, `description`, `audit_type`

**Relations:** `manager`, `standard`, `auditItems`

**Special Parameters:**
- `with_details=true` - Load all related audit items with full details

### Audit Items

**Base URL:** `/api/audit-items`

- `GET /api/audit-items` - List all audit items
- `POST /api/audit-items` - Create a new audit item
- `GET /api/audit-items/{id}` - Get a specific audit item
- `PUT /api/audit-items/{id}` - Update an audit item
- `DELETE /api/audit-items/{id}` - Delete an audit item
- `POST /api/audit-items/{id}/restore` - Restore a soft-deleted audit item

**Searchable Fields:** `notes`

**Relations:** `audit`, `auditable`, `dataRequests`

### Programs

**Base URL:** `/api/programs`

- `GET /api/programs` - List all programs
- `POST /api/programs` - Create a new program
- `GET /api/programs/{id}` - Get a specific program
- `PUT /api/programs/{id}` - Update a program
- `DELETE /api/programs/{id}` - Delete a program
- `POST /api/programs/{id}/restore` - Restore a soft-deleted program

**Searchable Fields:** `name`, `description`

**Relations:** `programManager`, `standards`, `controls`

### Risks

**Base URL:** `/api/risks`

- `GET /api/risks` - List all risks
- `POST /api/risks` - Create a new risk
- `GET /api/risks/{id}` - Get a specific risk
- `PUT /api/risks/{id}` - Update a risk
- `DELETE /api/risks/{id}` - Delete a risk
- `POST /api/risks/{id}/restore` - Restore a soft-deleted risk

**Searchable Fields:** `title`, `description`, `mitigation`

**Relations:** `implementations`

### Vendors

**Base URL:** `/api/vendors`

- `GET /api/vendors` - List all vendors
- `POST /api/vendors` - Create a new vendor
- `GET /api/vendors/{id}` - Get a specific vendor
- `PUT /api/vendors/{id}` - Update a vendor
- `DELETE /api/vendors/{id}` - Delete a vendor
- `POST /api/vendors/{id}/restore` - Restore a soft-deleted vendor

**Searchable Fields:** `name`, `description`, `contact_name`, `contact_email`

### Applications

**Base URL:** `/api/applications`

- `GET /api/applications` - List all applications
- `POST /api/applications` - Create a new application
- `GET /api/applications/{id}` - Get a specific application
- `PUT /api/applications/{id}` - Update an application
- `DELETE /api/applications/{id}` - Delete an application
- `POST /api/applications/{id}/restore` - Restore a soft-deleted application

**Searchable Fields:** `name`, `description`, `vendor.name`

**Relations:** `vendor`, `applicationOwner`

### Assets

**Base URL:** `/api/assets`

- `GET /api/assets` - List all assets
- `POST /api/assets` - Create a new asset
- `GET /api/assets/{id}` - Get a specific asset
- `PUT /api/assets/{id}` - Update an asset
- `DELETE /api/assets/{id}` - Delete an asset
- `POST /api/assets/{id}/restore` - Restore a soft-deleted asset

**Searchable Fields:** `name`, `description`, `asset_tag`

**Relations:** `assetOwner`, `implementations`

### Data Requests

**Base URL:** `/api/data-requests`

- `GET /api/data-requests` - List all data requests
- `POST /api/data-requests` - Create a new data request
- `GET /api/data-requests/{id}` - Get a specific data request
- `PUT /api/data-requests/{id}` - Update a data request
- `DELETE /api/data-requests/{id}` - Delete a data request

**Searchable Fields:** `request_text`

**Relations:** `audit`, `auditItem`, `responses`

### Data Request Responses

**Base URL:** `/api/data-request-responses`

- `GET /api/data-request-responses` - List all data request responses
- `POST /api/data-request-responses` - Create a new data request response
- `GET /api/data-request-responses/{id}` - Get a specific data request response
- `PUT /api/data-request-responses/{id}` - Update a data request response
- `DELETE /api/data-request-responses/{id}` - Delete a data request response

**Searchable Fields:** `response_text`

**Relations:** `dataRequest`, `requestee`

### File Attachments

**Base URL:** `/api/file-attachments`

- `GET /api/file-attachments` - List all file attachments
- `POST /api/file-attachments` - Create a new file attachment
- `GET /api/file-attachments/{id}` - Get a specific file attachment
- `PUT /api/file-attachments/{id}` - Update a file attachment
- `DELETE /api/file-attachments/{id}` - Delete a file attachment

**Searchable Fields:** `filename`, `original_filename`

## Response Formats

### Success Responses

**Index (List) Response:**
```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "http://example.com/api/resources?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "http://example.com/api/resources?page=5",
  "next_page_url": "http://example.com/api/resources?page=2",
  "path": "http://example.com/api/resources",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 75
}
```

**Show/Create/Update Response:**
```json
{
  "data": {
    "id": 1,
    "title": "Example Resource",
    ...
  }
}
```

**Delete Response:**
- Status: `204 No Content`
- Body: Empty

### Error Responses

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden:**
```json
{
  "message": "This action is unauthorized."
}
```

**404 Not Found:**
```json
{
  "message": "No query results for model..."
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field name is required."
    ]
  }
}
```

**429 Rate Limit Exceeded:**
```json
{
  "message": "Too Many Attempts."
}
```

## Example Usage

### Create a New User

```bash
curl -X POST "https://your-domain.com/api/users" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alice Johnson",
    "email": "alice@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "roles": ["Security Admin"]
  }'
```

### List Users with Roles

```bash
curl -X GET "https://your-domain.com/api/users?with=roles" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### List Controls with Search and Pagination

```bash
curl -X GET "https://your-domain.com/api/controls?search=encryption&per_page=10&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Create a New Standard

```bash
curl -X POST "https://your-domain.com/api/standards" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "NIST-800-53",
    "title": "NIST Special Publication 800-53",
    "description": "Security and Privacy Controls",
    "status": "active"
  }'
```

### Get Audit with Full Details

```bash
curl -X GET "https://your-domain.com/api/audits/1?with_details=true" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Update an Implementation

```bash
curl -X PUT "https://your-domain.com/api/implementations/5" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "implemented",
    "effectiveness": "effective"
  }'
```

### Delete a Risk

```bash
curl -X DELETE "https://your-domain.com/api/risks/3" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Restore a Deleted Control

```bash
curl -X POST "https://your-domain.com/api/controls/7/restore" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Security Best Practices

1. **Always use HTTPS** in production
2. **Keep tokens secure** - never commit them to version control
3. **Rotate tokens regularly** - generate new tokens periodically
4. **Use appropriate permissions** - follow the principle of least privilege
5. **Monitor API usage** - watch for unusual patterns or rate limit hits
6. **Validate all input** - the API performs server-side validation
7. **Handle errors gracefully** - check response status codes

## Support

For issues or questions about the API, please create an issue on the OpenGRC GitHub repository.
