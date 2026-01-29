<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $demoUsers = [
            ['name' => 'Sarah Chen', 'email' => 'sarah.chen@example.com', 'title' => 'Chief Information Security Officer'],
            ['name' => 'Marcus Johnson', 'email' => 'marcus.johnson@example.com', 'title' => 'Security Program Manager'],
            ['name' => 'Emily Rodriguez', 'email' => 'emily.rodriguez@example.com', 'title' => 'Compliance Analyst'],
            ['name' => 'David Kim', 'email' => 'david.kim@example.com', 'title' => 'IT Security Engineer'],
            ['name' => 'Jessica Thompson', 'email' => 'jessica.thompson@example.com', 'title' => 'Risk Manager'],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@example.com', 'title' => 'Internal Auditor'],
            ['name' => 'Amanda Garcia', 'email' => 'amanda.garcia@example.com', 'title' => 'Vendor Manager'],
            ['name' => 'James Wilson', 'email' => 'james.wilson@example.com', 'title' => 'Security Awareness Lead'],
            ['name' => 'Lisa Patel', 'email' => 'lisa.patel@example.com', 'title' => 'Privacy Officer'],
            ['name' => 'Robert Taylor', 'email' => 'robert.taylor@example.com', 'title' => 'IT Director'],
            ['name' => 'Jennifer Martinez', 'email' => 'jennifer.martinez@example.com', 'title' => 'Policy Analyst'],
            ['name' => 'Christopher Lee', 'email' => 'christopher.lee@example.com', 'title' => 'SOC Analyst'],
        ];

        foreach ($demoUsers as $userData) {
            $this->context->users[] = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
