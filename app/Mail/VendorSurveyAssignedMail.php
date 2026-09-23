<?php

namespace App\Mail;

use App\Models\Survey;
use App\Models\VendorUser;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSurveyAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $vendorName;

    public string $portalName;

    public string $surveyTitle;

    public ?string $dueDate;

    public string $portalUrl;

    public function __construct(Survey $survey, VendorUser $vendorUser)
    {
        $this->email = $vendorUser->email;
        $this->name = $vendorUser->name;
        $this->vendorName = $vendorUser->vendor->name;
        $this->portalName = setting('vendor_portal.portal_name', 'Vendor Portal');
        $this->surveyTitle = $survey->display_title;
        $this->dueDate = $survey->due_date?->format('F j, Y');
        $this->portalUrl = url('/vendor');
    }

    public function build()
    {
        $viewString = setting('mail.templates.vendor_survey_assigned_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'name' => $this->name,
            'email' => $this->email,
            'vendorName' => $this->vendorName,
            'portalName' => $this->portalName,
            'surveyTitle' => $this->surveyTitle,
            'dueDate' => $this->dueDate,
            'portalUrl' => $this->portalUrl,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.vendor_survey_assigned_subject'), [
                'surveyTitle' => $this->surveyTitle,
            ]))
            ->html($renderedView);
    }
}
