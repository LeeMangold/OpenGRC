<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditResource\Pages\EditAudit;
use App\Filament\Resources\AuditResource\Pages\ViewAudit;
use App\Models\Audit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
    }

    #[Test]
    public function additional_members_can_be_added_to_an_audit(): void
    {
        $audit = Audit::factory()->withManager($this->superAdmin)->create();
        $existingMember = User::factory()->create();
        $newMember = User::factory()->create();
        $audit->members()->attach($existingMember);

        $this->actingAs($this->superAdmin);

        Livewire::test(EditAudit::class, ['record' => $audit->getRouteKey()])
            ->assertFormFieldExists('members')
            ->set('data.members', [$existingMember->id, $newMember->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing(
            [$existingMember->id, $newMember->id],
            $audit->fresh()->members->pluck('id')->all()
        );
    }

    #[Test]
    public function member_search_returns_matching_users(): void
    {
        $audit = Audit::factory()->withManager($this->superAdmin)->create();
        $match = User::factory()->create(['name' => 'Zelda Auditor']);

        $this->actingAs($this->superAdmin);

        $component = Livewire::test(EditAudit::class, ['record' => $audit->getRouteKey()]);
        $results = $component->instance()->getSchema('form')->getComponent('members')->getSearchResults('Zelda');

        $this->assertSame('Zelda Auditor', $results[$match->id] ?? null);
    }

    #[Test]
    public function super_admin_can_take_ownership_of_an_audit(): void
    {
        $previousManager = User::factory()->create();
        $audit = Audit::factory()->withManager($previousManager)->create([
            'status' => WorkflowStatus::INPROGRESS,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ViewAudit::class, ['record' => $audit->getRouteKey()])
            ->assertActionVisible('take_ownership')
            ->callAction('take_ownership');

        $audit->refresh();
        $this->assertSame($this->superAdmin->id, $audit->manager_id);
        $this->assertTrue($audit->members->contains($previousManager));
    }

    #[Test]
    public function take_ownership_is_hidden_from_the_current_manager(): void
    {
        $audit = Audit::factory()->withManager($this->superAdmin)->create();

        $this->actingAs($this->superAdmin);

        Livewire::test(ViewAudit::class, ['record' => $audit->getRouteKey()])
            ->assertActionHidden('take_ownership');
    }

    #[Test]
    public function take_ownership_is_hidden_from_non_super_admins(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Regular User');
        $audit = Audit::factory()->withManager(User::factory()->create())->create();

        $this->actingAs($user);

        Livewire::test(ViewAudit::class, ['record' => $audit->getRouteKey()])
            ->assertActionHidden('take_ownership');
    }
}
