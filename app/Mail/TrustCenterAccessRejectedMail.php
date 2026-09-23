<?php

namespace App\Mail;

use App\Models\TrustCenterAccessRequest;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrustCenterAccessRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requesterName;

    public string $requesterEmail;

    public ?string $reviewNotes;

    public function __construct(TrustCenterAccessRequest $accessRequest)
    {
        $this->requesterName = $accessRequest->requester_name;
        $this->requesterEmail = $accessRequest->requester_email;
        $this->reviewNotes = $accessRequest->review_notes;
    }

    public function build()
    {
        $viewString = setting('mail.templates.trust_center_access_rejected_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'requesterName' => $this->requesterName,
            'requesterEmail' => $this->requesterEmail,
            'reviewNotes' => $this->reviewNotes,
        ]);

        return $this->from(setting('mail.from'), setting('general.name'))
            ->to($this->requesterEmail)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.trust_center_access_rejected_subject'), [
                'requesterName' => $this->requesterName,
            ]))
            ->html($renderedView);
    }
}
