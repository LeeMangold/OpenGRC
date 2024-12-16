<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;

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

        $renderedView = Blade::render($viewString, [
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
