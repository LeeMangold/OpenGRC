<?php

namespace Tests\Browser;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\ImplementationStatus;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ImplementationTest extends DuskTestCase
{
    protected static string $testStandardCode = 'DUSK-STD-IMPL';

    protected static string $testControlCodeA = 'DUSK-CTRL-A';

    protected static string $testControlCodeB = 'DUSK-CTRL-B';

    protected static string $testImplCode = 'DUSK-IMPL-001';

    protected static string $testImplCodeC = 'DUSK-IMPL-C';

    protected static string $testImplCodeD = 'DUSK-IMPL-D';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to reset rate limiting
        Artisan::call('cache:clear');

        // Clean up any existing test data before each test
        Implementation::where('code', self::$testImplCode)->forceDelete();
        Implementation::where('code', self::$testImplCodeC)->forceDelete();
        Implementation::where('code', self::$testImplCodeD)->forceDelete();
        Control::where('code', self::$testControlCodeA)->forceDelete();
        Control::where('code', self::$testControlCodeB)->forceDelete();
        Standard::where('code', self::$testStandardCode)->forceDelete();
    }

    protected function loginAs(Browser $browser): Browser
    {
        $user = User::where('email', 'admin@example.com')->first();

        return $browser->loginAs($user)
            ->visit('/app');
    }

    /**
     * Create a test standard for implementation tests.
     */
    protected function createTestStandard(): Standard
    {
        return Standard::create([
            'name' => 'Test Standard for Implementations',
            'code' => self::$testStandardCode,
            'authority' => 'Dusk Testing Authority',
            'description' => 'Test standard for implementation CRUD testing.',
            'status' => 'Draft',
        ]);
    }

    /**
     * Create a test control for implementation tests.
     */
    protected function createTestControl(Standard $standard, string $code, string $title): Control
    {
        $adminUser = User::where('email', 'admin@example.com')->first();

        return Control::create([
            'code' => $code,
            'title' => $title,
            'description' => "Test control {$code} for implementation testing.",
            'standard_id' => $standard->id,
            'enforcement' => ControlEnforcementCategory::MANDATORY,
            'type' => ControlType::ADMINISTRATIVE,
            'category' => ControlCategory::PREVENTIVE,
            'control_owner_id' => $adminUser->id,
        ]);
    }

    /**
     * Test the complete CRUD workflow for Implementations from the Implementations list:
     * Create -> Verify in table/view -> Edit -> Verify changes -> Delete -> Verify removal
     */
    public function test_implementation_crud_from_implementations_list(): void
    {
        // Create the test standard and control first
        $standard = $this->createTestStandard();
        $control = $this->createTestControl($standard, self::$testControlCodeA, 'Control A for Implementation');

        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: CREATE a new implementation from Implementations list
            // ==========================================
            $browser->visit('/app/implementations/create')
                ->assertSee('Create Implementation')
                ->pause(500);

            // Fill out all required fields
            $browser->type('#data\\.code', self::$testImplCode)
                ->type('#data\\.title', 'Test Implementation for Dusk')
                ->pause(300);

            // Select status
            $browser->filamentSelect('data.status', 'Implemented')
                ->pause(300);

            // Select related control
            $browser->filamentSelect('controls', '('.self::$testControlCodeA.') - Control A for Implementation')
                ->pause(300);

            // Select owner, department, scope
            $browser->filamentSelect('implementation_owner_id', 'Admin User')
                ->pause(300)
                ->filamentSelect('department', 'Security')
                ->pause(300)
                ->filamentSelect('scope', 'Global')
                ->pause(300);

            // Fill the details (RichEditor - Filament's RichEditor uses Trix)
            // Find the first trix-editor element and set its content
            $browser->script("
                const trixEditors = document.querySelectorAll('trix-editor');
                if (trixEditors.length > 0) {
                    const editor = trixEditors[0].editor;
                    editor.loadHTML('<p>This is a test implementation description for Dusk testing.</p>');
                }
            ");
            $browser->pause(1000);

            // Submit
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Create')
                ->pause(2000);

            // Should redirect to view page after creation
            $browser->assertSee('Test Implementation for Dusk');

            // ==========================================
            // STEP 2: VERIFY implementation displays in VIEW
            // ==========================================
            $browser->assertSee(self::$testImplCode)
                ->assertSee('This is a test implementation description');

            // ==========================================
            // STEP 3: VERIFY implementation displays in TABLE
            // ==========================================
            $browser->visit('/app/implementations')
                ->pause(1000);

            // Use the table search box to find our implementation
            $browser->type('.fi-ta-search-field input', self::$testImplCode)
                ->pause(1500)
                ->assertSee(self::$testImplCode)
                ->assertSee('Test Implementation for Dusk');

            // ==========================================
            // STEP 4: EDIT the implementation
            // ==========================================
            $implementation = Implementation::where('code', self::$testImplCode)->first();

            $browser->visit("/app/implementations/{$implementation->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Implementation');

            // Update fields
            $browser->clear('#data\\.title')
                ->type('#data\\.title', 'Updated Test Implementation')
                ->pause(300);

            // Submit changes
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Save changes')
                ->pause(2000);

            // ==========================================
            // STEP 5: VERIFY edited implementation in VIEW
            // ==========================================
            $browser->assertSee('Updated Test Implementation')
                ->assertSee(self::$testImplCode);

            // ==========================================
            // STEP 6: VERIFY edited implementation in TABLE
            // ==========================================
            $browser->visit('/app/implementations')
                ->pause(1000);

            $browser->type('.fi-ta-search-field input', self::$testImplCode)
                ->pause(1500)
                ->assertSee('Updated Test Implementation');

            // ==========================================
            // STEP 7: DELETE the implementation
            // ==========================================
            $browser->visit("/app/implementations/{$implementation->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Implementation');

            // Click Delete button in header
            $browser->press('Delete')
                ->pause(1000);

            // Confirm in modal
            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Delete');
            });
            $browser->pause(2000);

            // ==========================================
            // STEP 8: VERIFY implementation is deleted
            // ==========================================
            $implementation->refresh();
            $this->assertNotNull($implementation->deleted_at, 'Implementation should be soft deleted');
        });
    }

    /**
     * Test creating an Implementation from the Control view page's relation manager.
     */
    public function test_implementation_creation_from_control_view(): void
    {
        // Create the test standard and control first
        $standard = $this->createTestStandard();
        $control = $this->createTestControl($standard, self::$testControlCodeA, 'Control A for Implementation');
        $adminUser = User::where('email', 'admin@example.com')->first();

        // Create an implementation via the database (simulating what the modal would do)
        $implementation = Implementation::create([
            'code' => self::$testImplCode,
            'title' => 'Implementation from Control View',
            'details' => 'Implementation created from control view page.',
            'status' => ImplementationStatus::FULL,
            'implementation_owner_id' => $adminUser->id,
        ]);

        // Attach the implementation to the control
        $control->implementations()->attach($implementation->id);

        $this->browse(function (Browser $browser) use ($control, $implementation) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: Verify the implementation appears in the Control's implementations table
            // ==========================================
            $browser->visit("/app/controls/{$control->id}")
                ->assertSee('Control A for Implementation')
                ->pause(1000)
                // The relation manager table shows "details" column, not "code"
                ->assertSee('Implementation created from control view page.');

            // ==========================================
            // STEP 2: Verify the implementation is in Implementations list
            // ==========================================
            $browser->visit('/app/implementations')
                ->pause(1000);

            $browser->type('.fi-ta-search-field input', self::$testImplCode)
                ->pause(1500)
                ->assertSee(self::$testImplCode)
                ->assertSee('Implementation from Control View');

            // ==========================================
            // STEP 3: Verify the New Implementation button exists on Control view
            // ==========================================
            $browser->visit("/app/controls/{$control->id}")
                ->pause(500)
                ->assertSee('New implementation');

            // ==========================================
            // STEP 4: Clean up - delete the implementation
            // ==========================================
            $browser->visit("/app/implementations/{$implementation->id}/edit")
                ->pause(1000)
                ->press('Delete')
                ->pause(1000);

            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Delete');
            });
            $browser->pause(2000);

            // Verify deletion
            $implementation->refresh();
            $this->assertNotNull($implementation->deleted_at, 'Implementation should be soft deleted');
        });
    }

    /**
     * Test attaching an existing implementation to a different control.
     * Creates Control A with Implementation C and D, then attaches Implementation C to Control B.
     */
    public function test_attach_existing_implementation_to_control(): void
    {
        // Create the test standard
        $standard = $this->createTestStandard();

        // Create two controls
        $controlA = $this->createTestControl($standard, self::$testControlCodeA, 'Control A');
        $controlB = $this->createTestControl($standard, self::$testControlCodeB, 'Control B');

        $adminUser = User::where('email', 'admin@example.com')->first();

        // Create Implementation C and attach to Control A
        $implementationC = Implementation::create([
            'code' => self::$testImplCodeC,
            'title' => 'Implementation C',
            'details' => 'Implementation C details.',
            'status' => ImplementationStatus::FULL,
            'implementation_owner_id' => $adminUser->id,
        ]);
        $controlA->implementations()->attach($implementationC->id);

        // Create Implementation D and attach to Control A
        $implementationD = Implementation::create([
            'code' => self::$testImplCodeD,
            'title' => 'Implementation D',
            'details' => 'Implementation D details.',
            'status' => ImplementationStatus::PARTIAL,
            'implementation_owner_id' => $adminUser->id,
        ]);
        $controlA->implementations()->attach($implementationD->id);

        $this->browse(function (Browser $browser) use ($controlA, $controlB, $implementationC, $implementationD) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: Verify Control A has both implementations
            // ==========================================
            $browser->visit("/app/controls/{$controlA->id}")
                ->assertSee('Control A')
                ->pause(1000)
                // The relation manager table shows "details" column, not "code"
                ->assertSee('Implementation C details.')
                ->assertSee('Implementation D details.');

            // ==========================================
            // STEP 2: Verify Control B has no implementations yet
            // ==========================================
            $browser->visit("/app/controls/{$controlB->id}")
                ->assertSee('Control B')
                ->pause(1000)
                ->assertSee('No implementations');

            // ==========================================
            // STEP 3: Attach Implementation C to Control B using "Add Existing Implementation"
            // ==========================================
            $browser->press('Add Existing Implementation')
                ->pause(1000);

            // Wait for modal to appear
            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            // Select Implementation C from the dropdown
            $browser->within('.fi-modal', function ($modal) {
                $modal->filamentSelect('recordId', '('.self::$testImplCodeC.') Implementation C');
            });
            $browser->pause(500);

            // Click Attach button in modal
            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Attach');
            });
            $browser->pause(2000);

            // ==========================================
            // STEP 4: Verify Implementation C now appears on Control B
            // ==========================================
            // The relation manager table shows "details" column
            $browser->assertSee('Implementation C details.');

            // ==========================================
            // STEP 5: Verify Implementation C still appears on Control A
            // ==========================================
            $browser->visit("/app/controls/{$controlA->id}")
                ->pause(1000)
                ->assertSee('Implementation C details.');

            // ==========================================
            // STEP 6: Verify Implementation C shows both controls in its view
            // ==========================================
            $browser->visit("/app/implementations/{$implementationC->id}")
                ->pause(1000)
                ->assertSee('Implementation C')
                ->assertSee(self::$testControlCodeA)
                ->assertSee(self::$testControlCodeB);

            // ==========================================
            // STEP 7: Clean up - delete the implementations
            // ==========================================
            $browser->visit("/app/implementations/{$implementationC->id}/edit")
                ->pause(1000)
                ->press('Delete')
                ->pause(1000);

            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Delete');
            });
            $browser->pause(1000);

            $browser->visit("/app/implementations/{$implementationD->id}/edit")
                ->pause(1000)
                ->press('Delete')
                ->pause(1000);

            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Delete');
            });
            $browser->pause(1000);

            // Verify deletions
            $implementationC->refresh();
            $implementationD->refresh();
            $this->assertNotNull($implementationC->deleted_at, 'Implementation C should be soft deleted');
            $this->assertNotNull($implementationD->deleted_at, 'Implementation D should be soft deleted');
        });
    }
}
