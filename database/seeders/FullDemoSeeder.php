<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Program;
use App\Models\Standard;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Risk;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\Crypt;
use App\Enums\WorkflowStatus;
use App\Enums\Effectiveness;
use App\Enums\Applicability;
use App\Enums\ResponseStatus;

class FullDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();

        // 1. Call base seeders
        $this->call([
            SettingsSeeder::class,
            UserSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // 2. Create 10 users and assign roles
        $roles = [
            'Super Admin',
            'Security Admin',
            'Internal Auditor',
            'Regular User',
            'None',
        ];
        $users = User::factory()
            ->count(10)
            ->sequence(fn ($sequence) => [
                'email' => 'user'.($sequence->index + 1).'@example.com',
                'password' => bcrypt('password'),
                'password_reset_required' => false,
            ])
            ->create();

        // Assign roles to users
        foreach ($users as $i => $user) {
            $roleName = $roles[$i % count($roles)];
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // 3. Create 3 programs
        $programs = [];
        foreach (range(1, 3) as $i) {
            $programs[] = Program::factory()->create([
                'program_manager_id' => $users->random()->id,
            ]);
        }

        // 4. Create 5 standards
        $standards = [];
        foreach (range(1, 5) as $i) {
            $standards[] = Standard::factory()->create([
                'name' => 'Standard '.$i,
                'code' => 'STD-'.$i,
                'authority' => $faker->company(),
            ]);
        }

        // Attach standards to programs randomly
        foreach ($standards as $standard) {
            $program = collect($programs)->random();
            $program->standards()->attach($standard->id);
        }

        // 5. For each standard, create 3-7 controls
        $controls = [];
        foreach ($standards as $standard) {
            $numControls = rand(3, 7);
            for ($j = 1; $j <= $numControls; $j++) {
                $controls[] = Control::factory()->create([
                    'standard_id' => $standard->id,
                    'code' => $standard->code.'-C'.$j,
                ]);
            }
        }

        // 6. For each control, create 0-4 implementations and attach
        $implementations = [];
        foreach ($controls as $control) {
            $numImpl = rand(0, 4);
            $impls = [];
            for ($k = 1; $k <= $numImpl; $k++) {
                $impl = Implementation::factory()->create([
                    'code' => $control->code.'-I'.$k,
                ]);
                $impls[] = $impl;
                $implementations[] = $impl;
            }
            if ($impls) {
                $control->implementations()->attach(collect($impls)->pluck('id')->all());
            }
        }

        // 7. Create 5 audits
        $audits = [];
        foreach (range(1, 5) as $i) {
            $audits[] = Audit::factory()->create([
                'title' => 'Audit '.$i,
                'manager_id' => $users->random()->id,
                'status' => $i <= 3 ? WorkflowStatus::COMPLETED->value : WorkflowStatus::INPROGRESS->value,
            ]);
        }

        // 8. For each audit, create audit items for random controls/implementations
        foreach ($audits as $audit) {
            $numItems = rand(3, 7);
            for ($i = 0; $i < $numItems; $i++) {
                $control = collect($controls)->random();
                $user = $users->random();
                $auditItem = AuditItem::create([
                    'audit_id' => $audit->id,
                    'user_id' => $user->id,
                    'auditable_type' => Control::class,
                    'auditable_id' => $control->id,
                    'auditor_notes' => $faker->sentence(),
                    'status' => $audit->status === WorkflowStatus::COMPLETED->value
                        ? WorkflowStatus::COMPLETED->value
                        : Arr::random([
                            WorkflowStatus::INPROGRESS->value,
                            WorkflowStatus::NOTSTARTED->value,
                            WorkflowStatus::COMPLETED->value
                        ]),
                    'effectiveness' => Arr::random([
                        Effectiveness::EFFECTIVE->value,
                        Effectiveness::PARTIAL->value,
                        Effectiveness::INEFFECTIVE->value,
                        Effectiveness::UNKNOWN->value,
                    ]),
                    'applicability' => Arr::random([
                        Applicability::APPLICABLE->value,
                        Applicability::NOTAPPLICABLE->value,
                        Applicability::UNKNOWN->value,
                    ]),
                ]);

                // 9. Create a data request for each audit item
                $dataRequest = DataRequest::create([
                    'created_by_id' => $user->id,
                    'assigned_to_id' => $users->random()->id,
                    'audit_id' => $audit->id,
                    'audit_item_id' => $auditItem->id,
                    'status' => 'Pending',
                    'details' => $faker->sentence(),
                ]);

                // 10. Create a data request response for each data request
                DataRequestResponse::create([
                    'data_request_id' => $dataRequest->id,
                    'requester_id' => $user->id,
                    'requestee_id' => $users->random()->id,
                    'response' => $faker->paragraph(),
                    'status' => Arr::random([
                        ResponseStatus::PENDING->value,
                        ResponseStatus::RESPONDED->value,
                        ResponseStatus::REJECTED->value,
                        ResponseStatus::ACCEPTED->value,
                    ]),
                ]);
            }
        }

        // 11. Create 10 cyber risks and map to programs/implementations
        $risks = [];
        foreach (range(1, 10) as $i) {
            $risk = Risk::factory()->create([
                'name' => 'Cyber Risk '.$i,
            ]);
            // Attach to random programs
            $risk->programs()->attach(collect($programs)->random()->id);
            // Attach to random implementations
            if ($implementations) {
                $risk->implementations()->attach(collect($implementations)->random()->id);
            }
            $risks[] = $risk;
        }
    }
} 