<?php

namespace Tests\Unit;

use App\Enums\ResponseStatus;
use App\Models\DataRequestResponse;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_has_fillable_attributes(): void
    {
        $fillable = ['name', 'text', 'email', 'password'];
        $user = new User;

        $this->assertEquals($fillable, $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $hidden = ['password', 'remember_token'];
        $user = new User;

        $this->assertEquals($hidden, $user->getHidden());
    }

    public function test_password_is_cast_as_hashed(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        $this->assertEquals('hashed', $casts['password']);
    }

    public function test_email_verified_at_is_cast_as_datetime(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        $this->assertEquals('datetime', $casts['email_verified_at']);
    }

    public function test_last_activity_is_cast_as_datetime(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        $this->assertEquals('datetime', $casts['last_activity']);
    }

    public function test_update_last_activity_updates_timestamp(): void
    {
        $user = User::factory()->create();
        $originalLastActivity = $user->last_activity;

        $user->updateLastActivity();

        $user->refresh();
        $this->assertNotEquals($originalLastActivity, $user->last_activity);
        $this->assertNotNull($user->last_activity);
    }

    public function test_can_access_panel_returns_true(): void
    {
        $user = User::factory()->create();
        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_user_has_audits_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->audits());
    }

    public function test_user_has_todos_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->todos());
        $this->assertEquals('requestee_id', $user->todos()->getForeignKeyName());
        $this->assertInstanceOf(DataRequestResponse::class, $user->todos()->getRelated());
    }

    public function test_user_has_open_todos_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->openTodos());
        $this->assertEquals('requestee_id', $user->openTodos()->getForeignKeyName());
    }

    public function test_open_todos_filters_by_status(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->openTodos());

        // Check that the query contains the correct where conditions
        $query = $user->openTodos();
        $bindings = $query->getQuery()->getBindings();
        $this->assertTrue(in_array(ResponseStatus::PENDING->value, $bindings) || in_array(ResponseStatus::REJECTED->value, $bindings));
    }

    public function test_user_has_managed_programs_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->managedPrograms());
        $this->assertEquals('program_manager_id', $user->managedPrograms()->getForeignKeyName());
        $this->assertInstanceOf(Program::class, $user->managedPrograms()->getRelated());
    }

    public function test_managed_programs_relationship_works(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['program_manager_id' => $user->id]);

        $this->assertTrue($user->managedPrograms->contains($program));
        $this->assertEquals($user->id, $program->program_manager_id);
    }

    public function test_user_uses_soft_deletes(): void
    {
        $user = User::factory()->create(['name' => 'Soft Delete Test']);
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNull(User::find($userId));
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    public function test_user_has_activity_log_options(): void
    {
        $user = new User;
        $logOptions = $user->getActivitylogOptions();

        $this->assertInstanceOf(\Spatie\Activitylog\LogOptions::class, $logOptions);
    }

    public function test_user_logs_specific_attributes(): void
    {
        $user = new User;
        $logOptions = $user->getActivitylogOptions();

        $reflection = new \ReflectionClass($logOptions);
        $attributesProperty = $reflection->getProperty('logAttributes');
        $attributesProperty->setAccessible(true);
        $logAttributes = $attributesProperty->getValue($logOptions);

        $this->assertEquals(['name', 'email'], $logAttributes);
    }
}
