<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $password;

    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct($email, $name, $password)
    {
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
        $this->url = setting('general.url');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $viewString = setting('mail.templates.new_user_body');

        $renderedView = Blade::render($viewString, [
            'url' => $this->url,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(setting('mail.templates.new_user_subject'))
            ->html($renderedView);
    }
}
