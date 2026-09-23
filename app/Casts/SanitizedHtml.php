<?php

namespace App\Casts;

use App\Services\HtmlSanitizerService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Sanitizes rich text HTML on write, covering every save path (Filament forms,
 * REST API, MCP tools, imports). Reads return the stored value unchanged;
 * views must still render through @safeHtml since legacy rows may predate this cast.
 *
 * Only apply to fields that always hold HTML: plain text would get entity-encoded.
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return app(HtmlSanitizerService::class)->sanitize($value);
    }
}
