<?php

namespace App\Mail;

use App\Models\TrustCenterAccessRequest;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrustCenterAccessApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requesterName;

    public string $requesterEmail;

    public string $accessUrl;

    public int $expiryHours;

    public string $expiresAt;

    public int $documentCount;

    public function __construct(TrustCenterAccessRequest $accessRequest)
    {
        $this->requesterName = $accessRequest->requester_name;
        $this->requesterEmail = $accessRequest->requester_email;
        $this->expiryHours = (int) setting('trust_center.magic_link_expiry_hours', 24);
        $this->expiresAt = $accessRequest->access_expires_at->format('F j, Y \a\t g:i A');
        $this->accessUrl = $accessRequest->getAccessUrl();
        $this->documentCount = $accessRequest->documents()->count();
    }

    public function build()
    {
        $viewString = setting('mail.templates.trust_center_access_approved_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'requesterName' => $this->requesterName,
            'requesterEmail' => $this->requesterEmail,
            'accessUrl' => $this->accessUrl,
            'expiryHours' => $this->expiryHours,
            'expiresAt' => $this->expiresAt,
            'documentCount' => $this->documentCount,
        ]);

        return $this->from(setting('mail.from'), setting('general.name'))
            ->to($this->requesterEmail)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.trust_center_access_approved_subject'), [
                'requesterName' => $this->requesterName,
            ]))
            ->html($renderedView);
    }
}
