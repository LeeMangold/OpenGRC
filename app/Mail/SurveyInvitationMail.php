<?php

namespace App\Mail;

use App\Models\Survey;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SurveyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $surveyUrl;

    public string $surveyTitle;

    public ?string $dueDate;

    public ?string $description;

    /**
     * Create a new message instance.
     */
    public function __construct(Survey $survey)
    {
        $this->email = $survey->respondent_email;
        $this->name = $survey->respondent_name ?? 'Recipient';
        $this->surveyUrl = $survey->getPublicUrl();
        $this->surveyTitle = $survey->display_title;
        $this->dueDate = $survey->due_date?->format('F j, Y');
        $this->description = $survey->description ?? $survey->template?->description;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $viewString = setting('mail.templates.survey_invitation_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'name' => $this->name,
            'email' => $this->email,
            'surveyUrl' => $this->surveyUrl,
            'surveyTitle' => $this->surveyTitle,
            'dueDate' => $this->dueDate,
            'description' => $this->description,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.survey_invitation_subject'), [
                'surveyTitle' => $this->surveyTitle,
            ]))
            ->html($renderedView);
    }
}
