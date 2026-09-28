<?php

namespace App\Services;

use App\Http\Controllers\PdfHelper;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes user-supplied rich text before it is rendered as raw HTML.
 *
 * Reuses Filament's HtmlSanitizerConfig allowlist so custom Blade views render
 * rich text exactly like Filament's own ->html() entries, but lifts the input
 * length cap so large documents (e.g. policy bodies) are never truncated.
 */
class HtmlSanitizerService
{
    private ?HtmlSanitizer $sanitizer = null;

    public function sanitize(mixed $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return $this->sanitizer()->sanitize((string) $html);
    }

    /**
     * Sanitize, then inline images as data URIs for DomPDF. Sanitizing first
     * guarantees PdfHelper only ever sees allowlisted <img> markup.
     */
    public function sanitizeForPdf(mixed $html): string
    {
        return (string) PdfHelper::convertImagesToBase64($this->sanitize($html));
    }

    private function sanitizer(): HtmlSanitizer
    {
        return $this->sanitizer ??= new HtmlSanitizer(
            app(HtmlSanitizerConfig::class)->withMaxInputLength(-1)
        );
    }
}
