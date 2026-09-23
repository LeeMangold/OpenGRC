<?php

namespace App\Mail;

use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EvidenceRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
        $this->url = setting('general.url');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $viewString = setting('mail.templates.evidence_request_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'url' => $this->url,
            'name' => $this->name,
            'email' => $this->email,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(setting('mail.templates.evidence_request_subject'))
            ->html($renderedView);
    }
}
