<?php

namespace Database\Seeders\Demo;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoVendorsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedVendors();
        $this->seedVendorUsers();
        $this->seedVendorDocuments();
    }

    private function seedVendors(): void
    {
        $vendorsData = [
            ['name' => 'Microsoft Corporation', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::MEDIUM, 'description' => 'Cloud services, productivity software, and enterprise solutions provider.'],
            ['name' => 'Amazon Web Services', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::MEDIUM, 'description' => 'Cloud infrastructure and platform services provider.'],
            ['name' => 'Salesforce Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Customer relationship management platform provider.'],
            ['name' => 'Okta Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Identity and access management services provider.'],
            ['name' => 'Splunk Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Security information and event management provider.'],
            ['name' => 'CrowdStrike Holdings', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Endpoint protection and threat intelligence provider.'],
            ['name' => 'Atlassian Corporation', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Collaboration and project management tools provider.'],
            ['name' => 'Zoom Video Communications', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::MEDIUM, 'description' => 'Video conferencing and communication platform.'],
            ['name' => 'Slack Technologies', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Team collaboration and messaging platform.'],
            ['name' => 'DocuSign Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Electronic signature and agreement cloud provider.'],
            ['name' => 'Datadog Inc.', 'status' => VendorStatus::PENDING, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Cloud monitoring and analytics platform.'],
            ['name' => 'HashiCorp Inc.', 'status' => VendorStatus::PENDING, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Infrastructure automation software provider.'],
            ['name' => 'ServiceNow Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'IT service management and workflow automation platform.'],
            ['name' => 'Workday Inc.', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::LOW, 'description' => 'Enterprise cloud applications for finance and HR.'],
            ['name' => 'Oracle Corporation', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::MEDIUM, 'description' => 'Enterprise software and cloud solutions provider.'],
            ['name' => 'Internal Development', 'status' => VendorStatus::ACCEPTED, 'risk_rating' => VendorRiskRating::VERY_LOW, 'description' => 'Internally developed applications and legacy systems.'],
            ['name' => 'Acme Consulting Group', 'status' => VendorStatus::REJECTED, 'risk_rating' => VendorRiskRating::HIGH, 'description' => 'IT consulting firm - rejected due to security concerns.'],
        ];

        foreach ($vendorsData as $vendorData) {
            $this->context->vendors[] = Vendor::create([
                'name' => $vendorData['name'],
                'description' => $vendorData['description'],
                'url' => 'https://www.'.Str::slug($vendorData['name']).'.com',
                'vendor_manager_id' => $this->context->users[array_rand($this->context->users)]->id,
                'status' => $vendorData['status']->value,
                'risk_rating' => $vendorData['risk_rating']->value,
                'contact_name' => $this->context->faker->name(),
                'contact_email' => $this->context->faker->companyEmail(),
                'contact_phone' => $this->context->faker->phoneNumber(),
                'notes' => $this->context->faker->optional(0.5)->sentence(),
            ]);
        }
    }

    private function seedVendorUsers(): void
    {
        foreach (array_slice($this->context->vendors, 0, 8) as $vendor) {
            // Create 1-2 vendor portal users per vendor
            $numUsers = rand(1, 2);
            for ($i = 0; $i < $numUsers; $i++) {
                VendorUser::create([
                    'vendor_id' => $vendor->id,
                    'name' => $this->context->faker->name(),
                    'email' => $this->context->faker->unique()->companyEmail(),
                    'password' => Hash::make('vendor123'),
                    'email_verified_at' => $this->context->faker->optional(0.8)->dateTimeBetween('-6 months', 'now'),
                    'last_login_at' => $this->context->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
                    'is_primary' => $i === 0,
                ]);
            }
        }
    }

    private function seedVendorDocuments(): void
    {
        $documentTypes = VendorDocumentType::cases();

        foreach (array_slice($this->context->vendors, 0, 10) as $vendor) {
            // Create 2-4 documents per vendor
            $numDocs = rand(2, 4);
            $usedTypes = [];

            for ($i = 0; $i < $numDocs; $i++) {
                $docType = $documentTypes[array_rand($documentTypes)];
                while (in_array($docType, $usedTypes) && count($usedTypes) < count($documentTypes)) {
                    $docType = $documentTypes[array_rand($documentTypes)];
                }
                $usedTypes[] = $docType;

                $status = $this->context->faker->randomElement([
                    VendorDocumentStatus::APPROVED,
                    VendorDocumentStatus::APPROVED,
                    VendorDocumentStatus::APPROVED,
                    VendorDocumentStatus::PENDING,
                    VendorDocumentStatus::UNDER_REVIEW,
                ]);

                VendorDocument::create([
                    'vendor_id' => $vendor->id,
                    'document_type' => $docType,
                    'name' => $docType->getLabel().' - '.$vendor->name,
                    'description' => 'Compliance documentation for '.$vendor->name,
                    'file_path' => 'vendor-documents/'.Str::slug($vendor->name).'/'.Str::slug($docType->value).'.pdf',
                    'file_name' => Str::slug($docType->value).'.pdf',
                    'file_size' => rand(100000, 5000000),
                    'mime_type' => 'application/pdf',
                    'status' => $status,
                    'issue_date' => $this->context->faker->dateTimeBetween('-2 years', '-3 months'),
                    'expiration_date' => $this->context->faker->dateTimeBetween('+1 month', '+18 months'),
                    'reviewed_by' => $status !== VendorDocumentStatus::PENDING ? $this->context->users[array_rand($this->context->users)]->id : null,
                    'reviewed_at' => $status !== VendorDocumentStatus::PENDING ? now()->subDays(rand(1, 30)) : null,
                ]);
            }
        }
    }
}
