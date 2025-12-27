<?php

namespace App\Mail;

use App\Models\TrustCenterAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;

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

        $renderedView = Blade::render($viewString, [
            'requesterName' => $this->requesterName,
            'requesterEmail' => $this->requesterEmail,
            'reviewNotes' => $this->reviewNotes,
        ]);

        return $this->from(setting('mail.from'), setting('general.name'))
            ->to($this->requesterEmail)
            ->subject(Blade::render(setting('mail.templates.trust_center_access_rejected_subject'), [
                'requesterName' => $this->requesterName,
            ]))
            ->html($renderedView);
    }
}
