<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Adds security headers to responses including CSP, X-Content-Type-Options, etc.
     * CSP is configured for compatibility with Filament, Livewire, and Alpine.js.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers for all responses
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // CSP only for HTML responses
        if ($this->shouldAddCsp($response)) {
            $response->headers->set('Content-Security-Policy', $this->buildPolicy($request));
        }

        return $response;
    }

    /**
     * Determine if CSP header should be added to the response.
     */
    protected function shouldAddCsp(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    /**
     * Build the Content-Security-Policy directive string.
     */
    protected function buildPolicy(Request $request): string
    {
        $storageEndpoints = $this->getStorageEndpoints();
        $cdnSources = $this->getCdnSources($request);

        $directives = [
            // Default fallback - restrict to same origin
            "default-src 'self'",

            // Scripts: self + unsafe-inline/eval required for Livewire/Alpine.js
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'".$cdnSources['script'],

            // Styles: self + unsafe-inline required for Filament's dynamic styles
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",

            // Fonts: Google Fonts, Bunny Fonts + self
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",

            // Images: self + data URIs + ui-avatars for default avatars + external storage
            "img-src 'self' data: blob: https://ui-avatars.com".$storageEndpoints,

            // Forms can only submit to same origin
            "form-action 'self'",

            // Prevent site from being embedded in frames (clickjacking protection)
            "frame-ancestors 'self'",

            // Base URI restriction
            "base-uri 'self'",

            // Connect (XHR/WebSocket): same origin for Livewire + external storage for file uploads
            "connect-src 'self'".$storageEndpoints,

            // Workers: blob URLs required for Filament file uploads
            "worker-src 'self' blob:",

            // Object/embed restrictions
            "object-src 'none'",

            // Upgrade insecure requests in production
            ...$this->productionDirectives(),
        ];

        return implode('; ', array_filter($directives));
    }

    /**
     * Get CDN sources for public pages that use external scripts.
     *
     * Trust Center public pages use Tailwind CDN and Alpine.js CDN.
     */
    protected function getCdnSources(Request $request): array
    {
        $scriptSources = '';

        // Trust Center public pages need Tailwind CDN and Alpine.js CDN
        if ($request->is('trust', 'trust/*')) {
            $scriptSources = ' https://cdn.tailwindcss.com https://cdn.jsdelivr.net';
        }

        return [
            'script' => $scriptSources,
        ];
    }

    /**
     * Get external storage endpoints for CSP allowlist.
     */
    protected function getStorageEndpoints(): string
    {
        $endpoints = [];

        // Check S3/DigitalOcean Spaces endpoint
        $defaultDisk = config('filesystems.default');

        if (in_array($defaultDisk, ['s3', 'digitalocean'])) {
            $endpoint = config("filesystems.disks.{$defaultDisk}.endpoint");
            $url = config("filesystems.disks.{$defaultDisk}.url");

            if ($endpoint) {
                // Extract hostname from endpoint URL
                $parsed = parse_url($endpoint);
                if (! empty($parsed['host'])) {
                    $endpoints[] = $parsed['scheme'].'://'.$parsed['host'];
                    // Also allow wildcard for regional subdomains
                    $endpoints[] = $parsed['scheme'].'://*.'.$parsed['host'];
                }
            }

            if ($url && $url !== config('app.url').'/media') {
                $parsed = parse_url($url);
                if (! empty($parsed['host'])) {
                    $endpoints[] = $parsed['scheme'].'://'.$parsed['host'];
                }
            }
        }

        return $endpoints ? ' '.implode(' ', array_unique($endpoints)) : '';
    }

    /**
     * Get additional directives for production environment.
     */
    protected function productionDirectives(): array
    {
        if (app()->environment('production')) {
            return ['upgrade-insecure-requests'];
        }

        return [];
    }
}
