<?php

namespace App\Mail;

use App\Models\TrustCenterAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;

class TrustCenterAccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requesterName;

    public string $requesterEmail;

    public string $requesterCompany;

    public ?string $reason;

    public bool $ndaAgreed;

    public string $approvalUrl;

    public int $documentCount;

    public function __construct(TrustCenterAccessRequest $accessRequest)
    {
        $this->requesterName = $accessRequest->requester_name;
        $this->requesterEmail = $accessRequest->requester_email;
        $this->requesterCompany = $accessRequest->requester_company;
        $this->reason = $accessRequest->reason;
        $this->ndaAgreed = $accessRequest->nda_agreed;
        $this->documentCount = $accessRequest->documents()->count();
        $this->approvalUrl = route('filament.app.resources.trust-center-access-requests.view', ['record' => $accessRequest->id]);
    }

    public function build()
    {
        $viewString = setting('mail.templates.trust_center_access_request_body');

        $renderedView = Blade::render($viewString, [
            'requesterName' => $this->requesterName,
            'requesterEmail' => $this->requesterEmail,
            'requesterCompany' => $this->requesterCompany,
            'reason' => $this->reason ?? 'No reason provided',
            'ndaAgreed' => $this->ndaAgreed,
            'approvalUrl' => $this->approvalUrl,
            'documentCount' => $this->documentCount,
        ]);

        return $this->from(setting('mail.from'), setting('general.name'))
            ->subject(Blade::render(setting('mail.templates.trust_center_access_request_subject'), [
                'requesterName' => $this->requesterName,
                'requesterCompany' => $this->requesterCompany,
            ]))
            ->html($renderedView);
    }
}
