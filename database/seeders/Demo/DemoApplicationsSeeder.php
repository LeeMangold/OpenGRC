<?php

namespace Database\Seeders\Demo;

use App\Models\Application;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoApplicationsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $applicationsData = [
            ['name' => 'Microsoft 365', 'vendor_index' => 0, 'type' => 'SaaS', 'description' => 'Cloud-based productivity suite including Office apps, email, and collaboration tools', 'status' => 'Approved'],
            ['name' => 'Microsoft Azure', 'vendor_index' => 0, 'type' => 'SaaS', 'description' => 'Cloud computing platform for infrastructure and services', 'status' => 'Approved'],
            ['name' => 'AWS EC2', 'vendor_index' => 1, 'type' => 'SaaS', 'description' => 'Elastic cloud computing infrastructure', 'status' => 'Approved'],
            ['name' => 'AWS S3', 'vendor_index' => 1, 'type' => 'SaaS', 'description' => 'Object storage service', 'status' => 'Approved'],
            ['name' => 'Salesforce CRM', 'vendor_index' => 2, 'type' => 'SaaS', 'description' => 'Customer relationship management platform', 'status' => 'Approved'],
            ['name' => 'Okta Identity Cloud', 'vendor_index' => 3, 'type' => 'SaaS', 'description' => 'Identity and access management solution', 'status' => 'Approved'],
            ['name' => 'Splunk Enterprise', 'vendor_index' => 4, 'type' => 'Server', 'description' => 'Security information and event management (SIEM)', 'status' => 'Approved'],
            ['name' => 'CrowdStrike Falcon', 'vendor_index' => 5, 'type' => 'SaaS', 'description' => 'Cloud-native endpoint protection platform', 'status' => 'Approved'],
            ['name' => 'Jira Software', 'vendor_index' => 6, 'type' => 'SaaS', 'description' => 'Project and issue tracking software', 'status' => 'Approved'],
            ['name' => 'Confluence', 'vendor_index' => 6, 'type' => 'SaaS', 'description' => 'Team collaboration and documentation platform', 'status' => 'Approved'],
            ['name' => 'Zoom Meetings', 'vendor_index' => 7, 'type' => 'SaaS', 'description' => 'Video conferencing platform', 'status' => 'Approved'],
            ['name' => 'Slack Enterprise', 'vendor_index' => 8, 'type' => 'SaaS', 'description' => 'Team communication platform', 'status' => 'Approved'],
            ['name' => 'DocuSign eSignature', 'vendor_index' => 9, 'type' => 'SaaS', 'description' => 'Electronic signature solution', 'status' => 'Approved'],
            ['name' => 'GitHub Enterprise', 'vendor_index' => 0, 'type' => 'SaaS', 'description' => 'Code hosting and version control platform', 'status' => 'Approved'],
            ['name' => 'ServiceNow ITSM', 'vendor_index' => 12, 'type' => 'SaaS', 'description' => 'IT service management platform', 'status' => 'Approved'],
            ['name' => 'Workday HCM', 'vendor_index' => 13, 'type' => 'SaaS', 'description' => 'Human capital management system', 'status' => 'Approved'],
            ['name' => 'NetSuite ERP', 'vendor_index' => 14, 'type' => 'SaaS', 'description' => 'Enterprise resource planning system', 'status' => 'Approved'],
            ['name' => 'Legacy Billing System', 'vendor_index' => 15, 'type' => 'Server', 'description' => 'On-premise billing application - scheduled for retirement', 'status' => 'Limited'],
        ];

        foreach ($applicationsData as $appData) {
            $this->context->applications[] = Application::create([
                'name' => $appData['name'],
                'vendor_id' => $this->context->vendors[$appData['vendor_index']]->id,
                'owner_id' => $this->context->users[array_rand($this->context->users)]->id,
                'type' => $appData['type'],
                'description' => $appData['description'],
                'status' => $appData['status'],
                'url' => 'https://www.'.Str::slug($appData['name']).'.com',
            ]);
        }
    }
}
