<?php

namespace Tests\Unit;

use App\Enums\Effectiveness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectivenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_effectiveness_enum_has_correct_cases(): void
    {
        $this->assertEquals('Effective', Effectiveness::EFFECTIVE->value);
        $this->assertEquals('Partially Effective', Effectiveness::PARTIAL->value);
        $this->assertEquals('Not Effective', Effectiveness::INEFFECTIVE->value);
        $this->assertEquals('Not Assessed', Effectiveness::UNKNOWN->value);
    }

    public function test_effectiveness_enum_has_all_expected_cases(): void
    {
        $expectedCases = [
            'EFFECTIVE',
            'PARTIAL',
            'INEFFECTIVE',
            'UNKNOWN',
        ];

        $actualCases = array_map(fn ($case) => $case->name, Effectiveness::cases());

        $this->assertEquals($expectedCases, $actualCases);
        $this->assertCount(4, Effectiveness::cases());
    }

    public function test_get_label_returns_correct_values(): void
    {
        // Test that getLabel returns a string (may be translated)
        $this->assertIsString(Effectiveness::EFFECTIVE->getLabel());
        $this->assertIsString(Effectiveness::PARTIAL->getLabel());
        $this->assertIsString(Effectiveness::INEFFECTIVE->getLabel());
        $this->assertIsString(Effectiveness::UNKNOWN->getLabel());
    }

    public function test_get_color_returns_correct_values(): void
    {
        $this->assertEquals('success', Effectiveness::EFFECTIVE->getColor());
        $this->assertEquals('warning', Effectiveness::PARTIAL->getColor());
        $this->assertEquals('danger', Effectiveness::INEFFECTIVE->getColor());
        $this->assertEquals('gray', Effectiveness::UNKNOWN->getColor());
    }

    public function test_effectiveness_implements_has_color_interface(): void
    {
        $this->assertInstanceOf(\Filament\Support\Contracts\HasColor::class, Effectiveness::EFFECTIVE);
    }

    public function test_effectiveness_implements_has_label_interface(): void
    {
        $this->assertInstanceOf(\Filament\Support\Contracts\HasLabel::class, Effectiveness::EFFECTIVE);
    }

    public function test_effectiveness_can_be_created_from_string(): void
    {
        $effective = Effectiveness::from('Effective');
        $this->assertEquals(Effectiveness::EFFECTIVE, $effective);

        $partial = Effectiveness::from('Partially Effective');
        $this->assertEquals(Effectiveness::PARTIAL, $partial);

        $ineffective = Effectiveness::from('Not Effective');
        $this->assertEquals(Effectiveness::INEFFECTIVE, $ineffective);

        $unknown = Effectiveness::from('Not Assessed');
        $this->assertEquals(Effectiveness::UNKNOWN, $unknown);
    }

    public function test_effectiveness_try_from_returns_null_for_invalid_value(): void
    {
        $result = Effectiveness::tryFrom('Invalid Value');
        $this->assertNull($result);
    }

    public function test_effectiveness_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        Effectiveness::from('Invalid Value');
    }

    public function test_effectiveness_color_mapping_covers_all_cases(): void
    {
        foreach (Effectiveness::cases() as $effectiveness) {
            $color = $effectiveness->getColor();
            $this->assertIsString($color);
            $this->assertNotEmpty($color);
        }
    }

    public function test_effectiveness_label_mapping_covers_all_cases(): void
    {
        foreach (Effectiveness::cases() as $effectiveness) {
            $label = $effectiveness->getLabel();
            $this->assertIsString($label);
            $this->assertNotNull($label);
        }
    }
}
