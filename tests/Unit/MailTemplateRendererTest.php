<?php

namespace Tests\Unit;

use App\Services\MailTemplateRenderer;
use PHPUnit\Framework\TestCase;

class MailTemplateRendererTest extends TestCase
{
    public function test_substitutes_and_escapes_variables(): void
    {
        $this->assertSame(
            '<p>Hello &lt;b&gt;Bob&lt;/b&gt;, bob@example.com</p>',
            MailTemplateRenderer::html('<p>Hello {{ $name }}, {{$email}}</p>', ['name' => '<b>Bob</b>', 'email' => 'bob@example.com'])
        );
    }

    public function test_raw_output_is_not_escaped(): void
    {
        $this->assertSame('<p>Hi</p>', MailTemplateRenderer::html('{!! $description !!}', ['description' => '<p>Hi</p>']));
    }

    public function test_text_mode_does_not_escape_and_strips_newlines(): void
    {
        $this->assertSame(
            "Request from O'Brien Bcc: x@evil.test",
            MailTemplateRenderer::text('Request from {{ $name }}', ['name' => "O'Brien\r\nBcc: x@evil.test"])
        );
    }

    public function test_php_expressions_are_never_executed(): void
    {
        $marker = sys_get_temp_dir().'/opengrc_mail_template_'.uniqid();
        $payloads = [
            "{{ file_put_contents('{$marker}', 'pwned') }}",
            "{!! file_put_contents('{$marker}', 'pwned') !!}",
            "@php file_put_contents('{$marker}', 'pwned'); @endphp",
            "<?php file_put_contents('{$marker}', 'pwned'); ?>",
            "@if(file_put_contents('{$marker}', 'pwned')) x @endif",
            "{{ \$name ?? file_put_contents('{$marker}', 'pwned') }}",
            '{{ $name->__toString() }}',
        ];

        foreach ($payloads as $payload) {
            $this->assertSame($payload, MailTemplateRenderer::html($payload, ['name' => 'Bob']));
            $this->assertSame($payload, MailTemplateRenderer::text($payload, ['name' => 'Bob']));
        }

        $this->assertFileDoesNotExist($marker);
    }

    public function test_unknown_variables_are_left_untouched(): void
    {
        $this->assertSame('Hi {{ $secret }}', MailTemplateRenderer::text('Hi {{ $secret }}', []));
    }

    public function test_ternary_with_string_literals(): void
    {
        $template = '{{ $ndaAgreed ? "Yes" : "No" }}';

        $this->assertSame('Yes', MailTemplateRenderer::html($template, ['ndaAgreed' => true]));
        $this->assertSame('No', MailTemplateRenderer::html($template, ['ndaAgreed' => false]));
        $this->assertSame('Yes', MailTemplateRenderer::html('{{ $ndaAgreed ? &quot;Yes&quot; : &quot;No&quot; }}', ['ndaAgreed' => true]));
    }

    public function test_conditionals(): void
    {
        $template = '<p>Hi</p>@if($reviewNotes)<p>Notes: {{ $reviewNotes }}</p>@endif';

        $this->assertSame('<p>Hi</p><p>Notes: Missing NDA</p>', MailTemplateRenderer::html($template, ['reviewNotes' => 'Missing NDA']));
        $this->assertSame('<p>Hi</p>', MailTemplateRenderer::html($template, ['reviewNotes' => null]));
        $this->assertSame('<b>B</b>', MailTemplateRenderer::html('@if($flag)A@else<b>B</b>@endif', ['flag' => false]));
        $this->assertSame('outer inner', MailTemplateRenderer::html('@if($a)outer @if($b)inner@endif@endif', ['a' => true, 'b' => true]));
    }
}
