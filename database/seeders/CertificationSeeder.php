<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certifications = [
            [
                'name' => 'SOC 2 Type I',
                'code' => 'soc2-type1',
                'description' => 'Service Organization Control 2 Type I report examining the suitability of design of controls at a specific point in time.',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 10,
            ],
            [
                'name' => 'SOC 2 Type II',
                'code' => 'soc2-type2',
                'description' => 'Service Organization Control 2 Type II report examining the operational effectiveness of controls over a period of time.',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 20,
            ],
            [
                'name' => 'ISO 27001',
                'code' => 'iso27001',
                'description' => 'International standard for information security management systems (ISMS).',
                'icon' => 'heroicon-o-document-check',
                'sort_order' => 30,
            ],
            [
                'name' => 'ISO 27017',
                'code' => 'iso27017',
                'description' => 'Security controls for cloud services based on ISO 27002.',
                'icon' => 'heroicon-o-cloud',
                'sort_order' => 40,
            ],
            [
                'name' => 'ISO 27018',
                'code' => 'iso27018',
                'description' => 'Code of practice for protection of personally identifiable information (PII) in public clouds.',
                'icon' => 'heroicon-o-cloud',
                'sort_order' => 50,
            ],
            [
                'name' => 'ISO 22301',
                'code' => 'iso22301',
                'description' => 'Business continuity management systems standard.',
                'icon' => 'heroicon-o-arrow-path',
                'sort_order' => 60,
            ],
            [
                'name' => 'HIPAA',
                'code' => 'hipaa',
                'description' => 'Health Insurance Portability and Accountability Act compliance for protected health information.',
                'icon' => 'heroicon-o-heart',
                'sort_order' => 70,
            ],
            [
                'name' => 'GDPR',
                'code' => 'gdpr',
                'description' => 'General Data Protection Regulation compliance for EU personal data protection.',
                'icon' => 'heroicon-o-globe-europe-africa',
                'sort_order' => 80,
            ],
            [
                'name' => 'CCPA',
                'code' => 'ccpa',
                'description' => 'California Consumer Privacy Act compliance for California resident data privacy.',
                'icon' => 'heroicon-o-user-circle',
                'sort_order' => 90,
            ],
            [
                'name' => 'PCI-DSS',
                'code' => 'pci-dss',
                'description' => 'Payment Card Industry Data Security Standard for payment card data protection.',
                'icon' => 'heroicon-o-credit-card',
                'sort_order' => 100,
            ],
            [
                'name' => 'FedRAMP',
                'code' => 'fedramp',
                'description' => 'Federal Risk and Authorization Management Program for US government cloud services.',
                'icon' => 'heroicon-o-building-library',
                'sort_order' => 110,
            ],
            [
                'name' => 'StateRAMP',
                'code' => 'stateramp',
                'description' => 'State Risk and Authorization Management Program for state and local government cloud services.',
                'icon' => 'heroicon-o-building-office-2',
                'sort_order' => 120,
            ],
            [
                'name' => 'CMMC Level 1',
                'code' => 'cmmc-l1',
                'description' => 'Cybersecurity Maturity Model Certification Level 1 - Basic Cyber Hygiene.',
                'icon' => 'heroicon-o-shield-exclamation',
                'sort_order' => 130,
            ],
            [
                'name' => 'CMMC Level 2',
                'code' => 'cmmc-l2',
                'description' => 'Cybersecurity Maturity Model Certification Level 2 - Advanced Cyber Hygiene.',
                'icon' => 'heroicon-o-shield-exclamation',
                'sort_order' => 140,
            ],
            [
                'name' => 'CMMC Level 3',
                'code' => 'cmmc-l3',
                'description' => 'Cybersecurity Maturity Model Certification Level 3 - Expert Cyber Hygiene.',
                'icon' => 'heroicon-o-shield-exclamation',
                'sort_order' => 150,
            ],
            [
                'name' => 'CSA STAR',
                'code' => 'csa-star',
                'description' => 'Cloud Security Alliance Security, Trust, Assurance, and Risk program.',
                'icon' => 'heroicon-o-star',
                'sort_order' => 160,
            ],
            [
                'name' => 'SOX',
                'code' => 'sox',
                'description' => 'Sarbanes-Oxley Act compliance for financial reporting controls.',
                'icon' => 'heroicon-o-currency-dollar',
                'sort_order' => 170,
            ],
            [
                'name' => 'NIST 800-53',
                'code' => 'nist-800-53',
                'description' => 'NIST Special Publication 800-53 security and privacy controls for information systems.',
                'icon' => 'heroicon-o-document-text',
                'sort_order' => 180,
            ],
            [
                'name' => 'NIST CSF',
                'code' => 'nist-csf',
                'description' => 'NIST Cybersecurity Framework for improving critical infrastructure cybersecurity.',
                'icon' => 'heroicon-o-document-text',
                'sort_order' => 190,
            ],
            [
                'name' => 'CIS Controls',
                'code' => 'cis-controls',
                'description' => 'Center for Internet Security Critical Security Controls.',
                'icon' => 'heroicon-o-clipboard-document-check',
                'sort_order' => 200,
            ],
        ];

        foreach ($certifications as $certification) {
            Certification::firstOrCreate(
                ['code' => $certification['code']],
                array_merge($certification, [
                    'is_predefined' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
