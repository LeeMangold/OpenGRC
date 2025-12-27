<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // General Settings
            ['key' => 'trust_center.enabled', 'value' => json_encode(true)],
            ['key' => 'trust_center.name', 'value' => json_encode('Trust Center')],
            ['key' => 'trust_center.company_name', 'value' => json_encode('')],
            ['key' => 'trust_center.company_logo', 'value' => json_encode('')],

            // Magic Link Settings
            ['key' => 'trust_center.magic_link_expiry_hours', 'value' => json_encode(24)],

            // NDA Settings
            ['key' => 'trust_center.nda_required', 'value' => json_encode(true)],
            ['key' => 'trust_center.nda_text', 'value' => json_encode('<p>By requesting access to protected documents, you agree to keep all information confidential and not disclose it to any third party without prior written consent. You acknowledge that the documents you are requesting access to contain sensitive security and compliance information that must be protected.</p><p>You agree to:</p><ul><li>Use the information only for evaluating our security posture</li><li>Not share the documents with unauthorized parties</li><li>Delete the documents when they are no longer needed</li><li>Notify us immediately of any unauthorized disclosure</li></ul>')],

            // Email Templates
            ['key' => 'mail.templates.trust_center_access_request_subject', 'value' => json_encode('New Trust Center Access Request from {{ $requesterName }}')],
            ['key' => 'mail.templates.trust_center_access_request_body', 'value' => json_encode('<p>A new access request has been submitted to the Trust Center.</p><p><strong>Requester Details:</strong></p><ul><li>Name: {{ $requesterName }}</li><li>Email: {{ $requesterEmail }}</li><li>Company: {{ $requesterCompany }}</li></ul><p><strong>Reason for Access:</strong></p><p>{{ $reason }}</p><p><strong>NDA Agreed:</strong> {{ $ndaAgreed ? "Yes" : "No" }}</p><p><a href="{{ $approvalUrl }}">Review Request</a></p>')],

            ['key' => 'mail.templates.trust_center_access_approved_subject', 'value' => json_encode('Your Trust Center Access Request Has Been Approved')],
            ['key' => 'mail.templates.trust_center_access_approved_body', 'value' => json_encode('<p>Hello {{ $requesterName }},</p><p>Your request to access protected documents in our Trust Center has been approved.</p><p>Click the link below to access the documents:</p><p><a href="{{ $accessUrl }}">Access Protected Documents</a></p><p><strong>Important:</strong> This link will expire in {{ $expiryHours }} hours.</p><p>If you have any questions, please contact us.</p>')],

            ['key' => 'mail.templates.trust_center_access_rejected_subject', 'value' => json_encode('Your Trust Center Access Request')],
            ['key' => 'mail.templates.trust_center_access_rejected_body', 'value' => json_encode('<p>Hello {{ $requesterName }},</p><p>We have reviewed your request to access protected documents in our Trust Center.</p><p>Unfortunately, we are unable to approve your request at this time.</p>@if($reviewNotes)<p><strong>Notes:</strong> {{ $reviewNotes }}</p>@endif<p>If you have any questions or would like to discuss further, please contact us.</p>')],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'trust_center.enabled',
            'trust_center.name',
            'trust_center.company_name',
            'trust_center.company_logo',
            'trust_center.magic_link_expiry_hours',
            'trust_center.nda_required',
            'trust_center.nda_text',
            'mail.templates.trust_center_access_request_subject',
            'mail.templates.trust_center_access_request_body',
            'mail.templates.trust_center_access_approved_subject',
            'mail.templates.trust_center_access_approved_body',
            'mail.templates.trust_center_access_rejected_subject',
            'mail.templates.trust_center_access_rejected_body',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
