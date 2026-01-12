<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Authorize Application - {{ config('app.name', 'MCP Server') }}</title>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            max-width: 420px;
            width: 100%;
            padding: 2rem;
        }
        .icon { text-align: center; margin-bottom: 1rem; }
        .icon svg { width: 48px; height: 48px; color: #3b82f6; }
        h1 { font-size: 1.5rem; font-weight: 600; text-align: center; margin-bottom: 0.5rem; }
        .subtitle { color: #64748b; text-align: center; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .user-box { background: #f1f5f9; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .user-label { font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; }
        .user-email { font-weight: 500; }
        .permissions { margin-bottom: 1.5rem; }
        .permissions-label { font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .permission-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #64748b; }
        .permission-dot { width: 6px; height: 6px; background: #3b82f6; border-radius: 50%; }
        .buttons { display: flex; gap: 0.75rem; }
        .btn { flex: 1; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; }
        .btn-cancel { background: #f1f5f9; color: #475569; }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-auth { background: #3b82f6; color: white; }
        .btn-auth:hover { background: #2563eb; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
    </div>

    <h1>Authorize {{ $client->name }}</h1>
    <p class="subtitle">This application will be able to use available MCP functionality.</p>

    <div class="user-box">
        <div class="user-label">Logged in as:</div>
        <div class="user-email">{{ $user->email }}</div>
    </div>

    @if(count($scopes) > 0)
        <div class="permissions">
            <div class="permissions-label">Permissions:</div>
            @foreach($scopes as $scope)
                <div class="permission-item">
                    <span class="permission-dot"></span>
                    {{ $scope->description }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="buttons">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}" style="flex: 1;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-cancel" style="width: 100%;">Cancel</button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.approve') }}" style="flex: 1;" id="authorizeForm">
            @csrf
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-auth" style="width: 100%;" id="authorizeButton">
                <span id="authorizeText">Authorize</span>
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('authorizeForm');
        const button = document.getElementById('authorizeButton');
        const authorizeText = document.getElementById('authorizeText');

        form.addEventListener('submit', function(e) {
            button.disabled = true;
            button.style.opacity = '0.7';
            authorizeText.textContent = 'Authorizing...';
        });
    });
</script>
</body>
</html>
