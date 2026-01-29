<?php

namespace Database\Seeders\Demo;

use App\Enums\MitigationType;
use App\Enums\RiskStatus;
use App\Models\Mitigation;
use App\Models\Risk;
use Illuminate\Database\Seeder;

class DemoRisksSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedRisks();
        $this->seedMitigations();
    }

    private function seedRisks(): void
    {
        $risksData = [
            ['code' => 'RISK-001', 'name' => 'Ransomware Attack', 'description' => 'Risk of ransomware encrypting critical business data and systems.', 'inherent_likelihood' => 3, 'inherent_impact' => 5, 'residual_likelihood' => 2, 'residual_impact' => 4, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-002', 'name' => 'Data Breach via Phishing', 'description' => 'Risk of employees falling victim to phishing attacks leading to credential compromise.', 'inherent_likelihood' => 4, 'inherent_impact' => 4, 'residual_likelihood' => 3, 'residual_impact' => 3, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-003', 'name' => 'Third-Party Security Incident', 'description' => 'Risk of security breach at a vendor affecting our data or operations.', 'inherent_likelihood' => 3, 'inherent_impact' => 4, 'residual_likelihood' => 2, 'residual_impact' => 3, 'status' => RiskStatus::IN_PROGRESS],
            ['code' => 'RISK-004', 'name' => 'Insider Threat - Data Exfiltration', 'description' => 'Risk of malicious insider stealing sensitive data.', 'inherent_likelihood' => 2, 'inherent_impact' => 5, 'residual_likelihood' => 1, 'residual_impact' => 4, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-005', 'name' => 'Cloud Misconfiguration', 'description' => 'Risk of misconfigured cloud services exposing data publicly.', 'inherent_likelihood' => 3, 'inherent_impact' => 4, 'residual_likelihood' => 2, 'residual_impact' => 3, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-006', 'name' => 'Business Email Compromise', 'description' => 'Risk of attackers impersonating executives for fraudulent wire transfers.', 'inherent_likelihood' => 3, 'inherent_impact' => 4, 'residual_likelihood' => 2, 'residual_impact' => 3, 'status' => RiskStatus::NOT_ASSESSED],
            ['code' => 'RISK-007', 'name' => 'API Security Vulnerability', 'description' => 'Risk of vulnerabilities in APIs exposing sensitive data.', 'inherent_likelihood' => 3, 'inherent_impact' => 3, 'residual_likelihood' => 2, 'residual_impact' => 2, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-008', 'name' => 'DDoS Attack', 'description' => 'Risk of distributed denial of service attack impacting availability.', 'inherent_likelihood' => 3, 'inherent_impact' => 3, 'residual_likelihood' => 2, 'residual_impact' => 2, 'status' => RiskStatus::IN_PROGRESS],
            ['code' => 'RISK-009', 'name' => 'Supply Chain Compromise', 'description' => 'Risk of compromised software in the supply chain.', 'inherent_likelihood' => 2, 'inherent_impact' => 5, 'residual_likelihood' => 1, 'residual_impact' => 4, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-010', 'name' => 'Regulatory Non-Compliance', 'description' => 'Risk of failing to meet GDPR, CCPA, or other regulatory requirements.', 'inherent_likelihood' => 2, 'inherent_impact' => 4, 'residual_likelihood' => 1, 'residual_impact' => 3, 'status' => RiskStatus::ASSESSED],
            ['code' => 'RISK-011', 'name' => 'Unpatched Vulnerabilities', 'description' => 'Risk of exploitation due to delayed patching of critical vulnerabilities.', 'inherent_likelihood' => 4, 'inherent_impact' => 3, 'residual_likelihood' => 2, 'residual_impact' => 2, 'status' => RiskStatus::IN_PROGRESS],
            ['code' => 'RISK-012', 'name' => 'Physical Security Breach', 'description' => 'Risk of unauthorized physical access to data centers or offices.', 'inherent_likelihood' => 2, 'inherent_impact' => 3, 'residual_likelihood' => 1, 'residual_impact' => 2, 'status' => RiskStatus::CLOSED],
        ];

        foreach ($risksData as $riskData) {
            $inherentRisk = $riskData['inherent_likelihood'] * $riskData['inherent_impact'];
            $residualRisk = $riskData['residual_likelihood'] * $riskData['residual_impact'];

            $this->context->risks[] = Risk::create([
                'code' => $riskData['code'],
                'name' => $riskData['name'],
                'description' => $riskData['description'],
                'status' => $riskData['status'],
                'inherent_likelihood' => $riskData['inherent_likelihood'],
                'inherent_impact' => $riskData['inherent_impact'],
                'inherent_risk' => $inherentRisk,
                'residual_likelihood' => $riskData['residual_likelihood'],
                'residual_impact' => $riskData['residual_impact'],
                'residual_risk' => $residualRisk,
                'is_active' => true,
            ]);
        }
    }

    private function seedMitigations(): void
    {
        $mitigationStrategies = [
            MitigationType::MITIGATE,
            MitigationType::MITIGATE,
            MitigationType::MITIGATE,
            MitigationType::ACCEPT,
            MitigationType::TRANSFER,
            MitigationType::AVOID,
        ];

        $mitigationDescriptions = [
            'Implemented technical controls to reduce likelihood.',
            'Deployed monitoring and alerting to detect incidents early.',
            'Established incident response procedures for rapid containment.',
            'Risk accepted by management with documented justification.',
            'Risk transferred to third-party via cyber insurance policy.',
            'Avoided risk by discontinuing the risky activity.',
            'Implemented compensating controls to reduce impact.',
            'Enhanced training program to reduce human error.',
        ];

        foreach ($this->context->risks as $risk) {
            // Add 1-2 mitigations per risk
            $numMitigations = rand(1, 2);
            for ($i = 0; $i < $numMitigations; $i++) {
                Mitigation::create([
                    'mitigatable_type' => Risk::class,
                    'mitigatable_id' => $risk->id,
                    'description' => $mitigationDescriptions[array_rand($mitigationDescriptions)],
                    'strategy' => $mitigationStrategies[array_rand($mitigationStrategies)],
                    'date_implemented' => $this->context->faker->dateTimeBetween('-1 year', 'now'),
                ]);
            }
        }
    }
}
