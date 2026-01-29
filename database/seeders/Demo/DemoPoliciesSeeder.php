<?php

namespace Database\Seeders\Demo;

use App\Enums\DocumentType;
use App\Enums\PolicyExceptionStatus;
use App\Models\Policy;
use App\Models\PolicyException;
use Illuminate\Database\Seeder;

class DemoPoliciesSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedPolicies();
        $this->seedPolicyExceptions();
    }

    private function seedPolicies(): void
    {
        $policiesData = [
            [
                'code' => 'POL-SEC-001',
                'name' => 'Information Security Policy',
                'document_type' => DocumentType::Policy,
                'purpose' => 'Establishes the overall framework for information security management across the organization.',
                'body' => 'This policy defines our commitment to protecting information assets and outlines the responsibilities of all employees in maintaining security.',
            ],
            [
                'code' => 'POL-ACC-001',
                'name' => 'Acceptable Use Policy',
                'document_type' => DocumentType::Policy,
                'purpose' => 'Defines acceptable use of company information systems and resources.',
                'body' => 'All users must use company systems responsibly and in accordance with business purposes.',
            ],
            [
                'code' => 'POL-DAT-001',
                'name' => 'Data Classification Policy',
                'document_type' => DocumentType::Policy,
                'purpose' => 'Establishes data classification levels and handling requirements.',
                'body' => 'Data must be classified as Public, Internal, Confidential, or Restricted based on sensitivity.',
            ],
            [
                'code' => 'POL-PRI-001',
                'name' => 'Privacy Policy',
                'document_type' => DocumentType::Policy,
                'purpose' => 'Defines how personal information is collected, used, and protected.',
                'body' => 'We are committed to protecting the privacy of individuals and complying with applicable privacy laws.',
            ],
            [
                'code' => 'POL-INC-001',
                'name' => 'Incident Response Policy',
                'document_type' => DocumentType::Policy,
                'purpose' => 'Establishes the framework for responding to security incidents.',
                'body' => 'All security incidents must be reported immediately and handled according to defined procedures.',
            ],
            [
                'code' => 'PRO-CHG-001',
                'name' => 'Change Management Procedure',
                'document_type' => DocumentType::Procedure,
                'purpose' => 'Defines the process for managing changes to production systems.',
                'body' => 'All changes must go through the CAB approval process before implementation.',
            ],
            [
                'code' => 'PRO-ACC-001',
                'name' => 'Access Review Procedure',
                'document_type' => DocumentType::Procedure,
                'purpose' => 'Outlines the quarterly access review process.',
                'body' => 'Managers must review and certify access for their direct reports quarterly.',
            ],
            [
                'code' => 'STD-PWD-001',
                'name' => 'Password Standard',
                'document_type' => DocumentType::Standard,
                'purpose' => 'Defines password complexity and management requirements.',
                'body' => 'Passwords must be at least 14 characters with complexity requirements.',
            ],
            [
                'code' => 'STD-ENC-001',
                'name' => 'Encryption Standard',
                'document_type' => DocumentType::Standard,
                'purpose' => 'Specifies approved encryption algorithms and key management.',
                'body' => 'AES-256 for data at rest, TLS 1.2+ for data in transit.',
            ],
            [
                'code' => 'GDE-REM-001',
                'name' => 'Remote Work Security Guide',
                'document_type' => DocumentType::Guide,
                'purpose' => 'Provides guidance for secure remote work practices.',
                'body' => 'Employees working remotely must follow these security guidelines.',
            ],
        ];

        foreach ($policiesData as $policyData) {
            $this->context->policies[] = Policy::create([
                'code' => $policyData['code'],
                'name' => $policyData['name'],
                'document_type' => $policyData['document_type'],
                'purpose' => $policyData['purpose'],
                'body' => $policyData['body'],
                'effective_date' => $this->context->faker->dateTimeBetween('-2 years', '-3 months'),
                'owner_id' => $this->context->users[array_rand($this->context->users)]->id,
                'created_by' => $this->context->users[0]->id,
            ]);
        }
    }

    private function seedPolicyExceptions(): void
    {
        $exceptionsData = [
            [
                'name' => 'Legacy System MFA Exception',
                'description' => 'Exception for legacy billing system that cannot support MFA.',
                'justification' => 'System is EOL in 6 months. Vendor does not support MFA.',
                'risk_assessment' => 'Medium risk. Compensating controls in place.',
                'compensating_controls' => 'IP whitelisting, enhanced monitoring, dedicated service account.',
                'status' => PolicyExceptionStatus::Approved,
            ],
            [
                'name' => 'Contractor VPN Split-Tunnel',
                'description' => 'Allow split-tunnel VPN for offshore contractors.',
                'justification' => 'Required for contractors to access local resources.',
                'risk_assessment' => 'Low risk with compensating controls.',
                'compensating_controls' => 'Endpoint protection, DLP agent, limited access scope.',
                'status' => PolicyExceptionStatus::Approved,
            ],
            [
                'name' => 'Development Environment Encryption',
                'description' => 'Waive encryption requirement for development databases.',
                'justification' => 'Performance requirements for development testing.',
                'risk_assessment' => 'Low risk - no production data allowed in dev.',
                'compensating_controls' => 'Data masking, network isolation, access controls.',
                'status' => PolicyExceptionStatus::Pending,
            ],
            [
                'name' => 'USB Storage for Field Team',
                'description' => 'Allow USB storage devices for field service team.',
                'justification' => 'Required for transferring large files at customer sites.',
                'risk_assessment' => 'Medium risk. Data loss potential.',
                'compensating_controls' => 'Encrypted USB drives only, DLP scanning, device tracking.',
                'status' => PolicyExceptionStatus::Denied,
            ],
        ];

        foreach ($exceptionsData as $index => $exceptionData) {
            PolicyException::create([
                'policy_id' => $this->context->policies[$index % count($this->context->policies)]->id,
                'name' => $exceptionData['name'],
                'description' => $exceptionData['description'],
                'justification' => $exceptionData['justification'],
                'risk_assessment' => $exceptionData['risk_assessment'],
                'compensating_controls' => $exceptionData['compensating_controls'],
                'status' => $exceptionData['status'],
                'requested_date' => $this->context->faker->dateTimeBetween('-6 months', '-1 month'),
                'effective_date' => $exceptionData['status'] === PolicyExceptionStatus::Approved
                    ? $this->context->faker->dateTimeBetween('-1 month', 'now')
                    : null,
                'expiration_date' => $exceptionData['status'] === PolicyExceptionStatus::Approved
                    ? $this->context->faker->dateTimeBetween('+3 months', '+12 months')
                    : null,
                'requested_by' => $this->context->users[array_rand($this->context->users)]->id,
                'approved_by' => $exceptionData['status'] !== PolicyExceptionStatus::Pending
                    ? $this->context->users[0]->id
                    : null,
                'created_by' => $this->context->users[array_rand($this->context->users)]->id,
            ]);
        }
    }
}
