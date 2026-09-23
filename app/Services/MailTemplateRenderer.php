<?php

namespace App\Services;

/**
 * Renders admin-editable mail templates without evaluating them as code.
 *
 * Templates are stored in the settings table and must never reach Blade, which compiles
 * its input to PHP. This renderer understands only the small Blade-like subset used by
 * the shipped templates, and only resolves variables explicitly passed in:
 *
 *   {{ $var }}                   HTML-escaped value (raw in text mode)
 *   {!! $var !!}                 unescaped value
 *   {{ $var ? "yes" : "no" }}    choose between two string literals
 *
 *   @if($var) ... @else ... @endif
 *
 * Anything else, including expressions or function calls, is left in the output as literal text.
 */
class MailTemplateRenderer
{
    private const VAR = '\$([A-Za-z_][A-Za-z0-9_]*)';

    /**
     * Render a template destined for an HTML body.
     *
     * @param  array<string, mixed>  $data
     */
    public static function html(?string $template, array $data = []): string
    {
        return self::render($template ?? '', $data, true);
    }

    /**
     * Render a template destined for plain text, such as a subject line.
     *
     * @param  array<string, mixed>  $data
     */
    public static function text(?string $template, array $data = []): string
    {
        // Subjects are header values: collapse any line breaks to prevent header injection.
        return trim(preg_replace('/[\r\n]+/', ' ', self::render($template ?? '', $data, false)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function render(string $template, array $data, bool $escape): string
    {
        $template = self::renderConditionals($template, $data);

        // Raw output: {!! $var !!}
        $template = preg_replace_callback(
            '/\{!!\s*'.self::VAR.'\s*!!\}/',
            fn (array $m) => array_key_exists($m[1], $data) ? self::stringify($data[$m[1]]) : $m[0],
            $template
        );

        // Escaped output: {{ $var }} or {{ $var ? "a" : "b" }}
        return preg_replace_callback(
            '/\{\{(.*?)\}\}/s',
            function (array $m) use ($data, $escape) {
                $value = self::resolveExpression(self::decodeExpression($m[1]), $data);

                if ($value === null) {
                    return $m[0];
                }

                return $escape ? e($value) : $value;
            },
            $template
        );
    }

    /**
     * Resolve @if($var) ... [@else ...] @endif blocks, innermost first.
     *
     * @param  array<string, mixed>  $data
     */
    private static function renderConditionals(string $template, array $data): string
    {
        $pattern = '/@if\s*\(\s*'.self::VAR.'\s*\)((?:(?!@if\b).)*?)(?:@else\b((?:(?!@if\b).)*?))?@endif\b/s';

        do {
            $previous = $template;
            $template = preg_replace_callback(
                $pattern,
                fn (array $m) => ! empty($data[$m[1]] ?? null) ? $m[2] : ($m[3] ?? ''),
                $template
            );
        } while ($template !== $previous && $template !== null);

        return $template ?? '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveExpression(string $expression, array $data): ?string
    {
        $expression = trim($expression);

        if (preg_match('/^'.self::VAR.'$/', $expression, $m)) {
            return array_key_exists($m[1], $data) ? self::stringify($data[$m[1]]) : null;
        }

        $literal = '(?:"([^"]*)"|\'([^\']*)\')';
        if (preg_match('/^'.self::VAR.'\s*\?\s*'.$literal.'\s*:\s*'.$literal.'$/', $expression, $m)) {
            if (! array_key_exists($m[1], $data)) {
                return null;
            }

            return ! empty($data[$m[1]]) ? ($m[2] !== '' ? $m[2] : ($m[3] ?? '')) : ($m[4] !== '' ? $m[4] : ($m[5] ?? ''));
        }

        return null;
    }

    /**
     * The rich editor may store quotes inside placeholders as HTML entities.
     */
    private static function decodeExpression(string $expression): string
    {
        return html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '',
            is_scalar($value), $value instanceof \Stringable => (string) $value,
            default => '',
        };
    }
}
