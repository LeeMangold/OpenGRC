<?php

namespace Database\Seeders\Demo;

use App\Models\Program;
use Illuminate\Database\Seeder;

class DemoProgramsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $programsData = [
            [
                'name' => 'Enterprise Security Program',
                'description' => 'Comprehensive security program covering all aspects of information security across the organization. This program establishes the foundation for our security posture and ensures alignment with industry best practices and regulatory requirements.',
                'scope_status' => 'In Scope',
            ],
            [
                'name' => 'SOC 2 Compliance Program',
                'description' => 'Program dedicated to achieving and maintaining SOC 2 Type II compliance. Covers security, availability, processing integrity, confidentiality, and privacy trust service criteria.',
                'scope_status' => 'In Scope',
            ],
            [
                'name' => 'Vendor Risk Management Program',
                'description' => 'Third-party risk management program for evaluating, monitoring, and managing risks associated with vendors and service providers. Includes due diligence, ongoing monitoring, and periodic reassessments.',
                'scope_status' => 'In Scope',
            ],
            [
                'name' => 'Data Privacy Program',
                'description' => 'Privacy program ensuring compliance with GDPR, CCPA, and other privacy regulations. Covers data subject rights, consent management, and privacy impact assessments.',
                'scope_status' => 'In Scope',
            ],
            [
                'name' => 'Business Continuity Program',
                'description' => 'Program for ensuring business resilience through disaster recovery planning, business impact analysis, and continuity testing. Maintains operational capabilities during disruptions.',
                'scope_status' => 'In Scope',
            ],
            [
                'name' => 'ISO 27001 Certification Program',
                'description' => 'Information Security Management System (ISMS) program aligned with ISO 27001:2022 requirements. Pursuing certification to demonstrate security commitment to stakeholders.',
                'scope_status' => 'Pending Review',
            ],
        ];

        foreach ($programsData as $index => $programData) {
            $this->context->programs[] = Program::create([
                'name' => $programData['name'],
                'description' => $programData['description'],
                'program_manager_id' => $this->context->users[$index % count($this->context->users)]->id,
                'last_audit_date' => $this->context->faker->dateTimeBetween('-1 year', '-1 month'),
                'scope_status' => $programData['scope_status'],
            ]);
        }
    }
}
