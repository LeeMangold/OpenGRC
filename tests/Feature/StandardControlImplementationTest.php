<?php

namespace Tests\Feature;

use App\Enums\Applicability;
use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Enums\StandardStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Standard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandardControlImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_standards_with_controls_and_implementations(): void
    {
        // Create 10 standards
        for ($s = 1; $s <= 10; $s++) {
            $standard = Standard::create([
                'name' => "Test Security Standard {$s}.0",
                'code' => "TSS-{$s}.0",
                'authority' => "Test Authority {$s}",
                'status' => StandardStatus::IN_SCOPE,
                'reference_url' => "https://test-standard-{$s}.example.com",
                'description' => "This is test security standard {$s} for automated testing purposes.",
            ]);

            $this->assertDatabaseHas('standards', [
                'name' => "Test Security Standard {$s}.0",
                'code' => "TSS-{$s}.0",
            ]);

            // Create 10 controls for each standard
            for ($i = 1; $i <= 10; $i++) {
                $control = Control::create([
                    'standard_id' => $standard->id,
                    'title' => "Test Control {$s}-{$i}",
                    'code' => "TC-{$s}-{$i}",
                    'description' => "Description for Test Control {$s}-{$i}",
                    'discussion' => "Discussion notes for Test Control {$s}-{$i}",
                    'test' => "Test plan for Control {$s}-{$i}",
                    'type' => $this->getRandomEnum(ControlType::class),
                    'category' => $this->getRandomEnum(ControlCategory::class),
                    'enforcement' => $this->getRandomEnum(ControlEnforcementCategory::class),
                    'effectiveness' => $this->getRandomEnum(Effectiveness::class),
                    'applicability' => $this->getRandomEnum(Applicability::class),
                ]);

                $this->assertDatabaseHas('controls', [
                    'standard_id' => $standard->id,
                    'code' => "TC-{$s}-{$i}",
                ]);

                // Create 10 implementations for each control
                for ($j = 1; $j <= 10; $j++) {
                    $implementation = Implementation::create([
                        'code' => "IMPL-{$s}-{$i}-{$j}",
                        'title' => "Implementation {$j} for Control {$s}-{$i}",
                        'details' => "Detailed implementation steps for Control {$s}-{$i}, Implementation {$j}",
                        'notes' => "Internal notes for Implementation {$j} of Standard {$s}",
                        'status' => $this->getRandomEnum(ImplementationStatus::class),
                        'effectiveness' => $this->getRandomEnum(Effectiveness::class),
                    ]);

                    // Associate implementation with control
                    $control->implementations()->attach($implementation->id);

                    $this->assertDatabaseHas('implementations', [
                        'code' => "IMPL-{$s}-{$i}-{$j}",
                    ]);

                    $this->assertDatabaseHas('control_implementation', [
                        'control_id' => $control->id,
                        'implementation_id' => $implementation->id,
                    ]);
                }
            }
        }

        // Verify final counts
        $this->assertEquals(10, Standard::count());
        $this->assertEquals(100, Control::count());
        $this->assertEquals(1000, Implementation::count());
        $this->assertEquals(1000, \DB::table('control_implementation')->count());

        // Verify relationships for each standard
        $standards = Standard::with('controls.implementations')->get();
        foreach ($standards as $standard) {
            $this->assertEquals(10, $standard->controls->count());
            foreach ($standard->controls as $control) {
                $this->assertEquals(10, $control->implementations->count());
            }
        }
    }

    public function test_can_create_and_complete_audits(): void
    {
        // First create standards with controls (calling the previous test)
        $this->test_can_create_standards_with_controls_and_implementations();

        // Get all standards
        $standards = Standard::all();

        // Create 10 audits, one for each standard
        foreach ($standards as $index => $standard) {
            $auditNumber = $index + 1;
            $audit = Audit::create([
                'title' => "Compliance Audit {$auditNumber} for {$standard->name}",
                'description' => "Comprehensive audit of {$standard->name} controls and implementations",
                'status' => WorkflowStatus::INPROGRESS,
                'audit_type' => 'standards',
                'sid' => $standard->id,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'manager_id' => 1,
                'controls' => $standard->controls->pluck('id')->toArray(),
            ]);

            $this->assertDatabaseHas('audits', [
                'title' => "Compliance Audit {$auditNumber} for {$standard->name}",
                'status' => WorkflowStatus::INPROGRESS,
            ]);

            // Create audit items for each control in the standard
            foreach ($standard->controls as $control) {
                $auditItem = AuditItem::create([
                    'audit_id' => $audit->id,
                    'user_id' => 1,
                    'auditable_id' => $control->id,
                    'auditable_type' => Control::class,
                    'auditor_notes' => "Assessment notes for {$control->code}: Control has been reviewed and tested.",
                    'status' => WorkflowStatus::COMPLETED,
                    'effectiveness' => $this->getRandomEnum(Effectiveness::class),
                    'applicability' => $this->getRandomEnum(Applicability::class),
                ]);

                $this->assertDatabaseHas('audit_items', [
                    'audit_id' => $audit->id,
                    'auditable_id' => $control->id,
                    'status' => WorkflowStatus::COMPLETED,
                ]);
            }

            // Complete the audit
            AuditResource::completeAudit($audit);

            $this->assertDatabaseHas('audits', [
                'id' => $audit->id,
                'status' => WorkflowStatus::COMPLETED,
            ]);
        }

        // Verify final counts
        $this->assertEquals(10, Audit::count());
        // $this->assertEquals(100, AuditItem::count()); // 10 controls per standard * 10 standards

        // // Verify all audits are closed
        // $this->assertEquals(
        //     10,
        //     Audit::where('status', WorkflowStatus::COMPLETED)->count()
        // );

        // // Verify all audit items are completed
        // $this->assertEquals(
        //     100,
        //     AuditItem::where('status', WorkflowStatus::COMPLETED)->count()
        // );

        // // Verify relationships
        // $audits = Audit::with('auditItems')->get();
        // foreach ($audits as $audit) {
        //     $this->assertEquals(10, $audit->auditItems->count());
        //     $this->assertTrue($audit->auditItems->every(fn ($item) => $item->status === WorkflowStatus::COMPLETED));
        // }
    }

    /**
     * Helper function to get a random enum value
     */
    private function getRandomEnum(string $enumClass): mixed
    {
        $cases = $enumClass::cases();

        return $cases[array_rand($cases)];
    }
}
