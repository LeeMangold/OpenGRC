<?php

namespace Database\Seeders\Demo;

use App\Enums\AccessRequestStatus;
use App\Enums\TrustLevel;
use App\Models\TrustCenterAccessRequest;
use App\Models\TrustCenterContentBlock;
use App\Models\TrustCenterDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoTrustCenterSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedTrustCenterContent();
        $this->seedTrustCenterDocuments();
        $this->seedTrustCenterAccessRequests();
    }

    private function seedTrustCenterContent(): void
    {
        $contentBlocks = [
            [
                'slug' => 'welcome',
                'title' => 'Welcome to Our Trust Center',
                'content' => 'At OpenGRC, security and privacy are foundational to everything we do. Our Trust Center provides transparency into our security practices, compliance certifications, and data protection measures.',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 1,
            ],
            [
                'slug' => 'security-practices',
                'title' => 'Security Practices',
                'content' => 'We implement industry-leading security controls including encryption at rest and in transit, multi-factor authentication, continuous monitoring, and regular penetration testing. Our security team maintains 24/7 vigilance over our systems.',
                'icon' => 'heroicon-o-lock-closed',
                'sort_order' => 2,
            ],
            [
                'slug' => 'data-privacy',
                'title' => 'Data Privacy',
                'content' => 'Your data privacy is our priority. We are fully compliant with GDPR, CCPA, and other major privacy regulations. We never sell your data and maintain strict access controls on all personal information.',
                'icon' => 'heroicon-o-eye-slash',
                'sort_order' => 3,
            ],
            [
                'slug' => 'compliance',
                'title' => 'Compliance',
                'content' => 'We maintain SOC 2 Type II certification and are aligned with ISO 27001 standards. Regular third-party audits verify our security controls and practices meet the highest industry standards.',
                'icon' => 'heroicon-o-clipboard-document-check',
                'sort_order' => 4,
            ],
        ];

        foreach ($contentBlocks as $block) {
            TrustCenterContentBlock::create([
                'slug' => $block['slug'],
                'title' => $block['title'],
                'content' => $block['content'],
                'icon' => $block['icon'],
                'is_enabled' => true,
                'sort_order' => $block['sort_order'],
            ]);
        }
    }

    private function seedTrustCenterDocuments(): void
    {
        $documentsData = [
            ['name' => 'SOC 2 Type II Report 2024', 'trust_level' => TrustLevel::PROTECTED, 'requires_nda' => true, 'description' => 'Latest SOC 2 Type II audit report covering security, availability, and confidentiality.'],
            ['name' => 'Security Whitepaper', 'trust_level' => TrustLevel::PUBLIC, 'requires_nda' => false, 'description' => 'Overview of our security architecture and practices.'],
            ['name' => 'Penetration Test Executive Summary', 'trust_level' => TrustLevel::PROTECTED, 'requires_nda' => true, 'description' => 'Executive summary of annual penetration testing results.'],
            ['name' => 'Data Processing Agreement', 'trust_level' => TrustLevel::PUBLIC, 'requires_nda' => false, 'description' => 'Standard DPA for customers processing personal data.'],
            ['name' => 'Business Continuity Plan Summary', 'trust_level' => TrustLevel::PROTECTED, 'requires_nda' => true, 'description' => 'Summary of business continuity and disaster recovery capabilities.'],
            ['name' => 'Privacy Policy', 'trust_level' => TrustLevel::PUBLIC, 'requires_nda' => false, 'description' => 'Our privacy policy and data handling practices.'],
            ['name' => 'Subprocessor List', 'trust_level' => TrustLevel::PUBLIC, 'requires_nda' => false, 'description' => 'List of subprocessors used in service delivery.'],
            ['name' => 'Insurance Certificate', 'trust_level' => TrustLevel::PROTECTED, 'requires_nda' => false, 'description' => 'Cyber liability and professional indemnity insurance certificate.'],
        ];

        foreach ($documentsData as $index => $docData) {
            $doc = TrustCenterDocument::create([
                'name' => $docData['name'],
                'description' => $docData['description'],
                'trust_level' => $docData['trust_level'],
                'requires_nda' => $docData['requires_nda'],
                'file_path' => 'trust-center/'.Str::slug($docData['name']).'.pdf',
                'file_name' => Str::slug($docData['name']).'.pdf',
                'file_size' => rand(100000, 2000000),
                'mime_type' => 'application/pdf',
                'uploaded_by' => $this->context->users[0]->id,
                'valid_from' => now()->subMonths(rand(1, 6)),
                'valid_until' => now()->addMonths(rand(6, 18)),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
            $this->context->trustCenterDocuments[] = $doc;

            // Link to certifications
            if ($index < count($this->context->certifications)) {
                $doc->certifications()->attach($this->context->certifications[$index]->id);
            }
        }
    }

    private function seedTrustCenterAccessRequests(): void
    {
        $requestsData = [
            ['status' => AccessRequestStatus::APPROVED, 'nda_agreed' => true],
            ['status' => AccessRequestStatus::APPROVED, 'nda_agreed' => true],
            ['status' => AccessRequestStatus::PENDING, 'nda_agreed' => true],
            ['status' => AccessRequestStatus::PENDING, 'nda_agreed' => true],
            ['status' => AccessRequestStatus::REJECTED, 'nda_agreed' => false],
            ['status' => AccessRequestStatus::REVOKED, 'nda_agreed' => true],
        ];

        $protectedDocs = array_filter($this->context->trustCenterDocuments, fn ($doc) => $doc->trust_level === TrustLevel::PROTECTED);

        foreach ($requestsData as $reqData) {
            $request = TrustCenterAccessRequest::create([
                'requester_name' => $this->context->faker->name(),
                'requester_email' => $this->context->faker->companyEmail(),
                'requester_company' => $this->context->faker->company(),
                'reason' => 'Evaluating security posture for vendor assessment process.',
                'nda_agreed' => $reqData['nda_agreed'],
                'status' => $reqData['status'],
                'reviewed_by' => $reqData['status'] !== AccessRequestStatus::PENDING ? $this->context->users[0]->id : null,
                'reviewed_at' => $reqData['status'] !== AccessRequestStatus::PENDING ? now()->subDays(rand(1, 14)) : null,
                'review_notes' => $reqData['status'] === AccessRequestStatus::REJECTED ? 'NDA not accepted.' : null,
                'access_token' => $reqData['status'] === AccessRequestStatus::APPROVED ? Str::random(64) : null,
                'access_expires_at' => $reqData['status'] === AccessRequestStatus::APPROVED ? now()->addDays(7) : null,
                'access_count' => $reqData['status'] === AccessRequestStatus::APPROVED ? rand(1, 5) : 0,
            ]);

            // Link to protected documents
            if ($reqData['status'] === AccessRequestStatus::APPROVED && ! empty($protectedDocs)) {
                $docsToAttach = array_slice(array_keys($protectedDocs), 0, rand(2, 4));
                foreach ($docsToAttach as $key) {
                    $request->documents()->attach($protectedDocs[$key]->id);
                }
            }
        }
    }
}
