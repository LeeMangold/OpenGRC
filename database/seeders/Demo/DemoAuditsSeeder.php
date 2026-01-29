<?php

namespace Database\Seeders\Demo;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use Illuminate\Database\Seeder;

class DemoAuditsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        // Create main demo audit
        $mainAudit = Audit::create([
            'title' => 'Annual Security Standards Audit 2024',
            'description' => 'Comprehensive annual audit of security controls against OpenGRC-1.0 standards.',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subWeek(),
            'audit_type' => 'standards',
            'manager_id' => $this->context->users[0]->id,
            'program_id' => $this->context->programs[0]->id,
            'status' => WorkflowStatus::COMPLETED,
        ]);

        // Add audit items for each control
        foreach ($this->context->controls as $control) {
            $status = $this->context->faker->randomElement([
                WorkflowStatus::COMPLETED,
                WorkflowStatus::COMPLETED,
                WorkflowStatus::COMPLETED,
                WorkflowStatus::INPROGRESS,
            ]);

            $auditItem = AuditItem::create([
                'audit_id' => $mainAudit->id,
                'user_id' => $this->context->users[array_rand($this->context->users)]->id,
                'auditable_type' => Control::class,
                'auditable_id' => $control->id,
                'auditor_notes' => 'Control reviewed and tested. Evidence collected and documented.',
                'status' => $status,
                'effectiveness' => $status === WorkflowStatus::COMPLETED
                    ? $this->context->faker->randomElement([Effectiveness::EFFECTIVE, Effectiveness::EFFECTIVE, Effectiveness::PARTIAL])
                    : Effectiveness::UNKNOWN,
                'applicability' => Applicability::APPLICABLE,
            ]);

            // Create data request for each audit item
            $dataRequest = DataRequest::create([
                'code' => 'DR-'.$control->code.'-001',
                'created_by_id' => $this->context->users[0]->id,
                'assigned_to_id' => $this->context->users[array_rand($this->context->users)]->id,
                'audit_id' => $mainAudit->id,
                'audit_item_id' => $auditItem->id,
                'status' => 'Responded',
                'details' => 'Please provide evidence of the implementation of this control including screenshots, configuration files, or policy documents.',
            ]);

            DataRequestResponse::create([
                'requester_id' => $this->context->users[0]->id,
                'requestee_id' => $this->context->users[array_rand($this->context->users)]->id,
                'data_request_id' => $dataRequest->id,
                'response' => 'Evidence attached demonstrates full compliance with this control. Implementation is in place and operating effectively.',
            ]);
        }

        // Create additional audits in different states
        $additionalAudits = [
            [
                'title' => 'SOC 2 Type II Audit Q1 2025',
                'description' => 'Annual SOC 2 Type II examination for trust service criteria.',
                'audit_type' => 'standards',
                'status' => WorkflowStatus::INPROGRESS,
                'program_id' => $this->context->programs[1]->id,
            ],
            [
                'title' => 'Vendor Security Assessment - Q4 2024',
                'description' => 'Quarterly assessment of critical vendor security postures.',
                'audit_type' => 'implementation',
                'status' => WorkflowStatus::COMPLETED,
                'program_id' => $this->context->programs[2]->id,
            ],
            [
                'title' => 'Privacy Controls Review 2025',
                'description' => 'Review of privacy controls for GDPR and CCPA compliance.',
                'audit_type' => 'standards',
                'status' => WorkflowStatus::NOTSTARTED,
                'program_id' => $this->context->programs[3]->id,
            ],
        ];

        foreach ($additionalAudits as $auditData) {
            Audit::create([
                'title' => $auditData['title'],
                'description' => $auditData['description'],
                'start_date' => now()->subMonths(rand(0, 3)),
                'end_date' => now()->addMonths(rand(1, 3)),
                'audit_type' => $auditData['audit_type'],
                'manager_id' => $this->context->users[array_rand($this->context->users)]->id,
                'program_id' => $auditData['program_id'],
                'status' => $auditData['status'],
            ]);
        }
    }
}
