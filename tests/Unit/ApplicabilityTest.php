<?php

namespace Tests\Unit;

use App\Enums\Applicability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicability_enum_has_correct_cases(): void
    {
        $this->assertEquals('Applicable', Applicability::APPLICABLE->value);
        $this->assertEquals('Not Applicable', Applicability::NOTAPPLICABLE->value);
        $this->assertEquals('Partially Applicable', Applicability::PARTIALLYAPPLICABLE->value);
        $this->assertEquals('Unknown', Applicability::UNKNOWN->value);
    }

    public function test_applicability_enum_has_all_expected_cases(): void
    {
        $expectedCases = [
            'APPLICABLE',
            'NOTAPPLICABLE',
            'PARTIALLYAPPLICABLE',
            'UNKNOWN',
        ];

        $actualCases = array_map(fn ($case) => $case->name, Applicability::cases());

        $this->assertEquals($expectedCases, $actualCases);
        $this->assertCount(4, Applicability::cases());
    }

    public function test_get_label_returns_correct_values(): void
    {
        // Test that getLabel returns a string (may be translated)
        $this->assertNotNull(Applicability::APPLICABLE->getLabel());
        $this->assertNotNull(Applicability::NOTAPPLICABLE->getLabel());
        $this->assertNotNull(Applicability::PARTIALLYAPPLICABLE->getLabel());
        $this->assertNotNull(Applicability::UNKNOWN->getLabel());
    }

    public function test_get_color_returns_correct_values(): void
    {
        $this->assertEquals('success', Applicability::APPLICABLE->getColor());
        $this->assertEquals('danger', Applicability::NOTAPPLICABLE->getColor());
        $this->assertEquals('warning', Applicability::PARTIALLYAPPLICABLE->getColor());
        $this->assertEquals('secondary', Applicability::UNKNOWN->getColor());
    }

    public function test_applicability_implements_has_color_interface(): void
    {
        $this->assertInstanceOf(\Filament\Support\Contracts\HasColor::class, Applicability::APPLICABLE);
    }

    public function test_applicability_implements_has_label_interface(): void
    {
        $this->assertInstanceOf(\Filament\Support\Contracts\HasLabel::class, Applicability::APPLICABLE);
    }

    public function test_applicability_can_be_created_from_string(): void
    {
        $applicable = Applicability::from('Applicable');
        $this->assertEquals(Applicability::APPLICABLE, $applicable);

        $notApplicable = Applicability::from('Not Applicable');
        $this->assertEquals(Applicability::NOTAPPLICABLE, $notApplicable);

        $partiallyApplicable = Applicability::from('Partially Applicable');
        $this->assertEquals(Applicability::PARTIALLYAPPLICABLE, $partiallyApplicable);

        $unknown = Applicability::from('Unknown');
        $this->assertEquals(Applicability::UNKNOWN, $unknown);
    }

    public function test_applicability_try_from_returns_null_for_invalid_value(): void
    {
        $result = Applicability::tryFrom('Invalid Value');
        $this->assertNull($result);
    }

    public function test_applicability_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        Applicability::from('Invalid Value');
    }
}
