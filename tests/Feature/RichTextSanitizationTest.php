<?php

namespace Tests\Feature;

use App\Http\Controllers\PdfHelper;
use App\Models\DataRequestResponse;
use App\Models\Policy;
use App\Services\HtmlSanitizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class RichTextSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<p>Evidence <strong>attached</strong> <a href="https://example.com">link</a></p>'
        .'<img src=x onerror="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)">x</a>';

    /**
     * Views allowed to use {!! !!} because they only output developer-controlled markup.
     */
    private const RAW_OUTPUT_ALLOWLIST = [
        'filament/admin/pages/api-documentation.blade.php',
        'filament/pages/import-data-table.blade.php',
        'filament/resources/audit-resource/pages/import-irl-table.blade.php',
        'filament/widgets/text-widget.blade.php',
    ];

    private function assertSanitized(string $html): void
    {
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<strong>attached</strong>', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
    }

    #[Test]
    public function safe_html_directive_strips_script_vectors_and_keeps_formatting(): void
    {
        $this->assertSanitized(Blade::render('@safeHtml($html)', ['html' => self::PAYLOAD]));
    }

    #[Test]
    public function safe_html_directive_renders_null_as_empty_string(): void
    {
        $this->assertSame('', Blade::render('@safeHtml($html)', ['html' => null]));
    }

    #[Test]
    public function legacy_unsanitized_rows_are_sanitized_on_render(): void
    {
        $response = DataRequestResponse::factory()->create();
        DB::table('data_request_responses')->where('id', $response->id)->update(['response' => self::PAYLOAD]);

        $this->assertSanitized(Blade::render('@safeHtml($r->response)', ['r' => $response->fresh()]));
    }

    #[Test]
    public function data_request_response_is_sanitized_on_save(): void
    {
        $response = DataRequestResponse::factory()->create(['response' => self::PAYLOAD]);

        $this->assertSanitized(DB::table('data_request_responses')->where('id', $response->id)->value('response'));
    }

    #[Test]
    public function large_policy_bodies_are_not_truncated(): void
    {
        $body = str_repeat('<p>'.str_repeat('a', 1000).'</p>', 600);

        $policy = Policy::factory()->create(['body' => $body]);

        $this->assertSame($body, $policy->fresh()->body);
    }

    #[Test]
    public function views_do_not_render_raw_html_outside_the_allowlist(): void
    {
        $offenders = [];

        foreach ((new Finder)->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            if (! in_array(str_replace('\\', '/', $file->getRelativePathname()), self::RAW_OUTPUT_ALLOWLIST, true)
                && str_contains($file->getContents(), '{!!')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Use @safeHtml()/@safePdfHtml() instead of {!! !!} for user content.');
    }

    #[Test]
    public function pdf_html_inlines_public_images_after_sanitizing(): void
    {
        $html = app(HtmlSanitizerService::class)
            ->sanitizeForPdf('<p>Logo</p><img src="/img/logo.png" onerror="alert(1)">');

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public static function unsafeImageSources(): array
    {
        return [
            'absolute local path' => ['/etc/passwd'],
            'public path traversal' => ['/../.env'],
            'loopback' => ['http://127.0.0.1/image.png'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'private network' => ['http://10.0.0.1/image.png'],
            'ipv6 loopback' => ['http://[::1]/image.png'],
            'non-http scheme' => ['file:///etc/passwd'],
        ];
    }

    #[Test]
    #[DataProvider('unsafeImageSources')]
    public function pdf_image_inlining_refuses_unsafe_sources(string $src): void
    {
        $html = '<img src="'.$src.'">';

        $this->assertSame($html, PdfHelper::convertImagesToBase64($html));
    }
}
