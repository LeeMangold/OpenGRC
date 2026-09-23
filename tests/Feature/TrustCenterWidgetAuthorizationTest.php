<?php

namespace Tests\Feature;

use App\Enums\TrustLevel;
use App\Filament\Pages\TrustCenterManager;
use App\Filament\Widgets\TrustCenter\CertificationsWidget;
use App\Filament\Widgets\TrustCenter\ContentBlocksWidget;
use App\Filament\Widgets\TrustCenter\PendingAccessRequestsWidget;
use App\Filament\Widgets\TrustCenter\TrustCenterDocumentsWidget;
use App\Models\TrustCenterDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrustCenterWidgetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $accessManager;

    protected User $trustCenterManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->accessManager = User::factory()->create();
        $this->accessManager->assignRole(
            Role::create(['name' => 'Trust Access Only', 'guard_name' => 'web'])->givePermissionTo('Manage Trust Access')
        );

        $this->trustCenterManager = User::factory()->create();
        $this->trustCenterManager->assignRole(
            Role::create(['name' => 'Trust Center Only', 'guard_name' => 'web'])->givePermissionTo('Manage Trust Center')
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeDocument(array $attributes = []): TrustCenterDocument
    {
        return TrustCenterDocument::create([
            'name' => 'SOC 2 Report',
            'trust_level' => TrustLevel::PROTECTED,
            'requires_nda' => true,
            'is_active' => true,
            'file_path' => 'trust-center/soc2.pdf',
            'file_name' => 'soc2.pdf',
            'sort_order' => 1,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, array{string, string, mixed, mixed}>
     */
    public static function documentBulkActions(): array
    {
        return [
            'activate' => ['activate', 'is_active', false, true],
            'deactivate' => ['deactivate', 'is_active', true, false],
            'set_public' => ['set_public', 'trust_level', TrustLevel::PROTECTED, TrustLevel::PUBLIC],
            'set_protected' => ['set_protected', 'trust_level', TrustLevel::PUBLIC, TrustLevel::PROTECTED],
            'require_nda' => ['require_nda', 'requires_nda', false, true],
            'remove_nda' => ['remove_nda', 'requires_nda', true, false],
        ];
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function managementWidgets(): array
    {
        return [
            'documents' => [TrustCenterDocumentsWidget::class],
            'certifications' => [CertificationsWidget::class],
            'content blocks' => [ContentBlocksWidget::class],
        ];
    }

    #[Test]
    #[DataProvider('managementWidgets')]
    public function trust_access_user_cannot_load_management_widgets(string $widget): void
    {
        $this->actingAs($this->accessManager);

        Livewire::test($widget)->assertForbidden();
    }

    #[Test]
    #[DataProvider('managementWidgets')]
    public function trust_access_user_is_denied_widget_actions(string $widget): void
    {
        $this->actingAs($this->accessManager);

        $this->assertTrue((new $widget)->getDefaultActionAuthorizationResponse(BulkAction::make('set_public'))->denied());
    }

    #[Test]
    #[DataProvider('managementWidgets')]
    public function trust_center_manager_is_not_restricted_by_default(string $widget): void
    {
        $this->actingAs($this->trustCenterManager);

        $this->assertNull((new $widget)->getDefaultActionAuthorizationResponse(BulkAction::make('set_public')));
    }

    #[Test]
    #[DataProvider('documentBulkActions')]
    public function trust_center_manager_can_run_document_bulk_actions(string $action, string $attribute, mixed $from, mixed $to): void
    {
        $document = $this->makeDocument([$attribute => $from]);

        $this->actingAs($this->trustCenterManager);

        Livewire::test(TrustCenterDocumentsWidget::class)
            ->callTableBulkAction($action, [$document->id]);

        $this->assertEquals($to, $document->fresh()->{$attribute});
    }

    #[Test]
    public function trust_access_user_only_sees_access_requests_tab(): void
    {
        $this->actingAs($this->accessManager);

        $page = Livewire::withQueryParams(['activeTab' => 'documents'])
            ->test(TrustCenterManager::class);

        $this->assertSame(['access_requests'], array_keys($page->instance()->getTabs()));
        $this->assertSame([PendingAccessRequestsWidget::class], $page->instance()->getWidgets());
    }

    #[Test]
    public function trust_center_manager_sees_all_tabs(): void
    {
        $this->actingAs($this->trustCenterManager);

        $page = Livewire::test(TrustCenterManager::class);

        $this->assertSame(['documents', 'certifications', 'access_requests', 'content'], array_keys($page->instance()->getTabs()));
        $this->assertSame([TrustCenterDocumentsWidget::class], $page->instance()->getWidgets());
    }

    #[Test]
    public function both_roles_can_load_the_trust_center_page(): void
    {
        $this->actingAs($this->accessManager)
            ->get('/app/trust-center-manager?activeTab=documents')
            ->assertOk()
            ->assertDontSee('Add Document');

        $this->actingAs($this->trustCenterManager)
            ->get('/app/trust-center-manager')
            ->assertOk()
            ->assertSee('Add Document');
    }
}
