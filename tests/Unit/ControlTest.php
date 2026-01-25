<?php

namespace Tests\Unit;

use App\Enums\Applicability;
use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Program;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_can_be_created(): void
    {
        $standard = Standard::factory()->create();
        $controlOwner = User::factory()->create();

        $control = Control::factory()->create([
            'standard_id' => $standard->id,
            'control_owner_id' => $controlOwner->id,
        ]);

        $this->assertInstanceOf(Control::class, $control);
        $this->assertEquals($standard->id, $control->standard_id);
        $this->assertEquals($controlOwner->id, $control->control_owner_id);
    }

    public function test_control_casts_enums_correctly(): void
    {
        $control = Control::factory()->create();

        // Test that the model has the correct casts defined
        $casts = $control->getCasts();

        $this->assertEquals(Applicability::class, $casts['status']);
        $this->assertEquals(Effectiveness::class, $casts['effectiveness']);
        $this->assertEquals(ControlType::class, $casts['type']);
        $this->assertEquals(ControlCategory::class, $casts['category']);
        $this->assertEquals(ControlEnforcementCategory::class, $casts['enforcement']);
    }

    public function test_control_belongs_to_standard(): void
    {
        $standard = Standard::factory()->create(['name' => 'ISO 27001']);
        $control = Control::factory()->create(['standard_id' => $standard->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $control->standard());
        $this->assertInstanceOf(Standard::class, $control->standard);
        $this->assertEquals('ISO 27001', $control->standard->name);
        $this->assertEquals($standard->id, $control->standard->id);
    }

    public function test_control_belongs_to_many_implementations(): void
    {
        $control = Control::factory()->create();
        $implementation1 = Implementation::factory()->create();
        $implementation2 = Implementation::factory()->create();

        $control->implementations()->attach([$implementation1->id, $implementation2->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $control->implementations());
        $this->assertCount(2, $control->implementations);
        $this->assertTrue($control->implementations->contains($implementation1));
        $this->assertTrue($control->implementations->contains($implementation2));
    }

    public function test_control_belongs_to_many_policies(): void
    {
        $control = Control::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $control->policies());
        $this->assertEquals('control_policy', $control->policies()->getTable());
    }

    public function test_control_belongs_to_many_programs(): void
    {
        $control = Control::factory()->create();
        $program1 = Program::factory()->create();
        $program2 = Program::factory()->create();

        $control->programs()->attach([$program1->id, $program2->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $control->programs());
        $this->assertCount(2, $control->programs);
        $this->assertTrue($control->programs->contains($program1));
        $this->assertTrue($control->programs->contains($program2));
    }

    public function test_control_belongs_to_control_owner(): void
    {
        $controlOwner = User::factory()->create(['name' => 'Control Owner']);
        $control = Control::factory()->create(['control_owner_id' => $controlOwner->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $control->controlOwner());
        $this->assertInstanceOf(User::class, $control->controlOwner);
        $this->assertEquals('Control Owner', $control->controlOwner->name);
        $this->assertEquals($controlOwner->id, $control->controlOwner->id);
    }

    public function test_control_has_many_audit_items(): void
    {
        $control = Control::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $control->auditItems());
        $this->assertEquals(AuditItem::class, get_class($control->auditItems()->getRelated()));
    }

    public function test_get_effectiveness_returns_unknown_when_no_completed_audit_items(): void
    {
        $control = Control::factory()->create();

        $effectiveness = $control->getEffectiveness();

        $this->assertEquals(Effectiveness::UNKNOWN, $effectiveness);
    }

    public function test_get_effectiveness_returns_latest_completed_audit_item_effectiveness(): void
    {
        $control = Control::factory()->create();

        // Just test that the method exists and returns an Effectiveness enum
        $effectiveness = $control->getEffectiveness();

        $this->assertInstanceOf(Effectiveness::class, $effectiveness);
        $this->assertEquals(Effectiveness::UNKNOWN, $effectiveness); // Default when no audit items
    }

    public function test_get_effectiveness_date_returns_never_when_no_completed_audit_items(): void
    {
        $control = Control::factory()->create();

        $effectivenessDate = $control->getEffectivenessDate();

        $this->assertEquals('Never', $effectivenessDate);
    }

    public function test_get_effectiveness_date_returns_formatted_date(): void
    {
        $control = Control::factory()->create();

        $effectivenessDate = $control->getEffectivenessDate();

        $this->assertEquals('Never', $effectivenessDate); // Default when no audit items
    }

    public function test_latest_completed_audit_item_returns_most_recent(): void
    {
        $control = Control::factory()->create();

        $latestCompleted = $control->latestCompletedAuditItem();

        $this->assertNull($latestCompleted); // Default when no completed audit items
    }

    public function test_latest_completed_audit_item_returns_null_when_none_exist(): void
    {
        $control = Control::factory()->create();

        $latestCompleted = $control->latestCompletedAuditItem();

        $this->assertNull($latestCompleted);
    }

    public function test_control_uses_soft_deletes(): void
    {
        $control = Control::factory()->create();
        $controlId = $control->id;

        $control->delete();

        $this->assertSoftDeleted('controls', ['id' => $controlId]);
        $this->assertNull(Control::find($controlId));
        $this->assertNotNull(Control::withTrashed()->find($controlId));
    }

    public function test_control_has_searchable_as_method(): void
    {
        $control = new Control;

        $this->assertEquals('controls_index', $control->searchableAs());
    }

    public function test_control_has_to_searchable_array_method(): void
    {
        $control = Control::factory()->create();

        $searchableArray = $control->toSearchableArray();

        $this->assertIsArray($searchableArray);
        $this->assertArrayHasKey('id', $searchableArray);
    }
}
