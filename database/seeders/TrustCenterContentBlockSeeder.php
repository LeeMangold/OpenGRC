<?php

namespace Database\Seeders;

use App\Models\TrustCenterContentBlock;
use Illuminate\Database\Seeder;

class TrustCenterContentBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contentBlocks = [
            [
                'slug' => 'overview',
                'title' => 'Overview',
                'content' => '<p>Welcome to our Trust Center. We are committed to maintaining the highest standards of security and compliance to protect your data.</p><p>This page provides transparency into our security practices, certifications, and compliance status.</p>',
                'is_enabled' => true,
                'sort_order' => 10,
                'icon' => 'heroicon-o-information-circle',
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'content' => '<h3>What certifications do you hold?</h3><p>Please see the certifications section below for our current certifications and compliance status.</p><h3>How do I request access to protected documents?</h3><p>Click the "Request Access" button on any protected document. You will need to provide your contact information and agree to our NDA before access is granted.</p><h3>How long does access approval take?</h3><p>Access requests are typically reviewed within 1-2 business days.</p>',
                'is_enabled' => false,
                'sort_order' => 20,
                'icon' => 'heroicon-o-question-mark-circle',
            ],
            [
                'slug' => 'subprocessors',
                'title' => 'Subprocessors',
                'content' => '<p>We use the following subprocessors to deliver our services:</p><ul><li><strong>Amazon Web Services (AWS)</strong> - Cloud infrastructure and hosting</li><li><strong>Google Cloud Platform</strong> - Data analytics and machine learning</li><li><strong>Stripe</strong> - Payment processing</li></ul><p>All subprocessors are bound by data processing agreements and maintain appropriate security certifications.</p>',
                'is_enabled' => false,
                'sort_order' => 30,
                'icon' => 'heroicon-o-server-stack',
            ],
            [
                'slug' => 'security_practices',
                'title' => 'Security Practices',
                'content' => '<h3>Data Encryption</h3><p>All data is encrypted at rest using AES-256 and in transit using TLS 1.3.</p><h3>Access Controls</h3><p>We implement role-based access controls and multi-factor authentication for all system access.</p><h3>Vulnerability Management</h3><p>Regular vulnerability assessments and penetration testing are conducted by third-party security firms.</p><h3>Incident Response</h3><p>We maintain a comprehensive incident response plan with 24/7 security monitoring.</p>',
                'is_enabled' => false,
                'sort_order' => 40,
                'icon' => 'heroicon-o-shield-check',
            ],
            [
                'slug' => 'data_handling',
                'title' => 'Data Handling',
                'content' => '<h3>Data Collection</h3><p>We collect only the data necessary to provide our services.</p><h3>Data Storage</h3><p>Data is stored in secure, SOC 2 certified data centers with geographic redundancy.</p><h3>Data Retention</h3><p>Data is retained only for as long as necessary to fulfill the purposes for which it was collected.</p><h3>Data Deletion</h3><p>Upon request or contract termination, customer data is securely deleted within 30 days.</p>',
                'is_enabled' => false,
                'sort_order' => 50,
                'icon' => 'heroicon-o-circle-stack',
            ],
            [
                'slug' => 'compliance_roadmap',
                'title' => 'Compliance Roadmap',
                'content' => '<h3>Current Focus</h3><ul><li>Maintaining SOC 2 Type II certification</li><li>Annual ISO 27001 surveillance audit</li></ul><h3>Upcoming Initiatives</h3><ul><li>HIPAA attestation renewal</li><li>GDPR compliance assessment</li></ul><p>For specific timeline questions, please contact our security team.</p>',
                'is_enabled' => false,
                'sort_order' => 60,
                'icon' => 'heroicon-o-map',
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Information',
                'content' => '<h3>Security Team</h3><p>For security-related inquiries:</p><p>Email: security@example.com</p><h3>Privacy Team</h3><p>For privacy-related inquiries:</p><p>Email: privacy@example.com</p><h3>Vulnerability Disclosure</h3><p>To report a security vulnerability, please email: security@example.com</p>',
                'is_enabled' => false,
                'sort_order' => 70,
                'icon' => 'heroicon-o-envelope',
            ],
        ];

        foreach ($contentBlocks as $block) {
            TrustCenterContentBlock::firstOrCreate(
                ['slug' => $block['slug']],
                $block
            );
        }
    }
}
