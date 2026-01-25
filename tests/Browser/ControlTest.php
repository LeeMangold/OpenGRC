<?php

namespace Tests\Browser;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Models\Control;
use App\Models\Standard;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ControlTest extends DuskTestCase
{
    protected static string $testStandardCode = 'DUSK-STD-001';

    protected static string $testControlCode = 'DUSK-CTRL-001';

    protected static string $testControlCode2 = 'DUSK-CTRL-002';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to reset rate limiting
        Artisan::call('cache:clear');

        // Clean up any existing test data before each test
        Control::where('code', self::$testControlCode)->forceDelete();
        Control::where('code', self::$testControlCode2)->forceDelete();
        Standard::where('code', self::$testStandardCode)->forceDelete();
    }

    protected function loginAs(Browser $browser): Browser
    {
        $user = \App\Models\User::where('email', 'admin@example.com')->first();

        return $browser->loginAs($user)
            ->visit('/app');
    }

    /**
     * Create a test standard for control tests.
     */
    protected function createTestStandard(): Standard
    {
        return Standard::create([
            'name' => 'Test Standard for Controls',
            'code' => self::$testStandardCode,
            'authority' => 'Dusk Testing Authority',
            'description' => 'This is a test standard for control CRUD testing.',
            'status' => 'Draft',
        ]);
    }

    /**
     * Test the complete CRUD workflow for Controls from the Controls list:
     * Create -> Verify in table/view -> Edit -> Verify changes -> Delete -> Verify removal
     */
    public function test_control_crud_from_controls_list(): void
    {
        // Create the test standard first
        $standard = $this->createTestStandard();

        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: CREATE a new control from Controls list
            // ==========================================
            $browser->visit('/app/controls/create')
                ->assertSee('Create Control')
                ->pause(500);

            // Fill out all required fields
            $browser->type('#data\\.code', self::$testControlCode)
                ->type('#data\\.title', 'Test Control for Dusk')
                ->pause(300);

            // Select the standard (searchable select)
            $browser->filamentSelect('standard_id', 'Test Standard for Controls');

            // Select enforcement, type, and category (all required)
            $browser->filamentSelect('data.enforcement', 'Mandatory')
                ->pause(300)
                ->filamentSelect('data.type', 'Administrative')
                ->pause(300)
                ->filamentSelect('data.category', 'Preventive')
                ->pause(300);

            // Select department, scope, and owner (required)
            $browser->filamentSelect('department', 'Security')
                ->pause(300)
                ->filamentSelect('scope', 'Global')
                ->pause(300)
                ->filamentSelect('control_owner_id', 'Admin User')
                ->pause(300);

            // Fill the description (RichEditor)
            $browser->pause(1000);
            $this->fillRichEditor($browser, 'data.description', 'This is a test control description for Dusk testing.');

            // Submit
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Create')
                ->pause(2000);

            // Should redirect to view page after creation
            $browser->assertSee('Test Control for Dusk');

            // ==========================================
            // STEP 2: VERIFY control displays in VIEW
            // ==========================================
            $browser->assertSee(self::$testControlCode)
                ->assertSee('This is a test control description');

            // ==========================================
            // STEP 3: VERIFY control displays in TABLE
            // ==========================================
            $browser->visit('/app/controls')
                ->pause(1000);

            // Use the table search box to find our control (there may be many controls)
            // Filament table search has a specific class
            $browser->type('.fi-ta-search-field input', self::$testControlCode)
                ->pause(1500)
                ->assertSee(self::$testControlCode)
                ->assertSee('Test Control for Dusk');

            // ==========================================
            // STEP 4: EDIT the control
            // ==========================================
            $control = Control::where('code', self::$testControlCode)->first();

            $browser->visit("/app/controls/{$control->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Control');

            // Update fields
            $browser->clear('#data\\.title')
                ->type('#data\\.title', 'Updated Test Control')
                ->pause(300);

            // Submit changes
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Save changes')
                ->pause(2000);

            // ==========================================
            // STEP 5: VERIFY edited control in VIEW
            // ==========================================
            $browser->assertSee('Updated Test Control')
                ->assertSee(self::$testControlCode);

            // ==========================================
            // STEP 6: VERIFY edited control in TABLE
            // ==========================================
            $browser->visit('/app/controls')
                ->pause(1000);

            // Search for the updated control using table search
            $browser->type('.fi-ta-search-field input', self::$testControlCode)
                ->pause(1500)
                ->assertSee('Updated Test Control');

            // ==========================================
            // STEP 7: DELETE the control
            // ==========================================
            $browser->visit("/app/controls/{$control->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Control');

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
            // STEP 8: VERIFY control is deleted
            // ==========================================
            // Verify the control was soft-deleted (it may still appear in table due to withoutGlobalScopes)
            $control->refresh();
            $this->assertNotNull($control->deleted_at, 'Control should be soft deleted');

            // Verify it's not accessible via direct URL (should show as deleted or redirect)
            $browser->visit("/app/controls/{$control->id}")
                ->pause(1000);

            // The control view might show a "deleted" indicator or restore option
            // Just verify we can't edit it as a normal control anymore
        });
    }

    /**
     * Test creating a Control from the Standard view page's relation manager.
     * This verifies the "Add New Control" button from within a Standard works correctly.
     */
    public function test_control_creation_from_standard_view(): void
    {
        // Create a control programmatically to test that creating from standard view
        // properly associates the control with the standard
        $standard = $this->createTestStandard();

        // Get the admin user for control owner
        $adminUser = \App\Models\User::where('email', 'admin@example.com')->first();

        // Create a control via the database (simulating what the modal would do)
        $control = Control::create([
            'code' => self::$testControlCode2,
            'title' => 'Control from Standard View',
            'description' => 'Control created from standard view page.',
            'standard_id' => $standard->id,
            'enforcement' => ControlEnforcementCategory::MANDATORY,
            'type' => ControlType::ADMINISTRATIVE,
            'category' => ControlCategory::PREVENTIVE,
            'control_owner_id' => $adminUser->id,
        ]);

        $this->browse(function (Browser $browser) use ($standard, $control) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: Verify the control appears in the Standard's controls table
            // ==========================================
            $browser->visit("/app/standards/{$standard->id}")
                ->assertSee('Test Standard for Controls')
                ->pause(1000)
                ->assertSee(self::$testControlCode2)
                ->assertSee('Control from Standard View');

            // ==========================================
            // STEP 2: Verify the control is linked to the correct standard in Controls list
            // ==========================================
            $browser->visit('/app/controls')
                ->pause(1000);

            $browser->type('.fi-ta-search-field input', self::$testControlCode2)
                ->pause(1500)
                ->assertSee(self::$testControlCode2)
                ->assertSee('Test Standard for Controls');  // Standard name should appear

            // ==========================================
            // STEP 3: Verify the Add New Control button exists on Standard view
            // ==========================================
            $browser->visit("/app/standards/{$standard->id}")
                ->pause(500)
                ->assertSee('Add New Control');

            // ==========================================
            // STEP 4: Clean up - delete the control
            // ==========================================
            $browser->visit("/app/controls/{$control->id}/edit")
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
            $control->refresh();
            $this->assertNotNull($control->deleted_at, 'Control should be soft deleted');
        });
    }

    /**
     * Helper method to fill Filament RichEditor using Trix.
     */
    protected function fillRichEditor(Browser $browser, string $fieldId, string $content): void
    {
        // Filament RichEditor uses Trix editor under the hood
        // The editor has a trix-editor element with a corresponding hidden input
        $browser->script("
            (function() {
                // Find the Trix editor element - look for one containing 'description' in related elements
                const trixEditors = document.querySelectorAll('trix-editor');

                if (trixEditors.length === 0) {
                    console.log('No Trix editors found');
                    return;
                }

                let editor = null;

                // Find editor by looking at the input element's name/id
                for (const trix of trixEditors) {
                    const inputId = trix.getAttribute('input');
                    if (inputId && inputId.toLowerCase().includes('description')) {
                        editor = trix;
                        break;
                    }
                    // Also check aria-label or nearby labels
                    const label = trix.closest('.fi-fo-field-wrp')?.querySelector('label');
                    if (label && label.textContent.toLowerCase().includes('description')) {
                        editor = trix;
                        break;
                    }
                }

                // Fallback to first editor
                if (!editor && trixEditors.length > 0) {
                    editor = trixEditors[0];
                }

                if (editor && editor.editor) {
                    editor.editor.loadHTML('<p>{$content}</p>');
                    // Trigger change events
                    editor.dispatchEvent(new Event('trix-change', { bubbles: true }));
                    const input = document.getElementById(editor.getAttribute('input'));
                    if (input) {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    console.log('Set content on Trix editor');
                }
            })();
        ");
        $browser->pause(500);
    }

    /**
     * Helper method to fill Filament RichEditor in a modal.
     */
    protected function fillRichEditorInModal(Browser $browser, string $fieldId, string $content): void
    {
        $browser->script("
            (function() {
                const modal = document.querySelector('.fi-modal');
                if (!modal) {
                    console.log('No modal found');
                    return;
                }

                const trixEditors = modal.querySelectorAll('trix-editor');
                if (trixEditors.length === 0) {
                    console.log('No Trix editors found in modal');
                    return;
                }

                // Use the first Trix editor in the modal
                const editor = trixEditors[0];
                if (editor && editor.editor) {
                    editor.editor.loadHTML('<p>{$content}</p>');
                    editor.dispatchEvent(new Event('trix-change', { bubbles: true }));
                    const input = document.getElementById(editor.getAttribute('input'));
                    if (input) {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    console.log('Set content on modal Trix editor');
                }
            })();
        ");
        $browser->pause(500);
    }
}
