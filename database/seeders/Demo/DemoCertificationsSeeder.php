<?php

namespace Database\Seeders\Demo;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class DemoCertificationsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $certificationsData = [
            ['name' => 'SOC 2 Type II', 'code' => 'SOC2', 'description' => 'Service Organization Control 2 Type II Report', 'icon' => 'heroicon-o-shield-check', 'is_predefined' => true],
            ['name' => 'ISO 27001', 'code' => 'ISO27001', 'description' => 'Information Security Management System certification', 'icon' => 'heroicon-o-academic-cap', 'is_predefined' => true],
            ['name' => 'ISO 27017', 'code' => 'ISO27017', 'description' => 'Cloud Security certification', 'icon' => 'heroicon-o-cloud', 'is_predefined' => true],
            ['name' => 'HIPAA', 'code' => 'HIPAA', 'description' => 'Health Insurance Portability and Accountability Act compliance', 'icon' => 'heroicon-o-heart', 'is_predefined' => true],
            ['name' => 'PCI DSS', 'code' => 'PCIDSS', 'description' => 'Payment Card Industry Data Security Standard', 'icon' => 'heroicon-o-credit-card', 'is_predefined' => true],
            ['name' => 'GDPR', 'code' => 'GDPR', 'description' => 'General Data Protection Regulation compliance', 'icon' => 'heroicon-o-globe-alt', 'is_predefined' => true],
            ['name' => 'CSA STAR', 'code' => 'CSASTAR', 'description' => 'Cloud Security Alliance STAR certification', 'icon' => 'heroicon-o-star', 'is_predefined' => true],
            ['name' => 'FedRAMP', 'code' => 'FEDRAMP', 'description' => 'Federal Risk and Authorization Management Program', 'icon' => 'heroicon-o-building-office-2', 'is_predefined' => true],
        ];

        foreach ($certificationsData as $index => $certData) {
            $this->context->certifications[] = Certification::create([
                'name' => $certData['name'],
                'code' => $certData['code'],
                'description' => $certData['description'],
                'icon' => $certData['icon'],
                'is_predefined' => $certData['is_predefined'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
