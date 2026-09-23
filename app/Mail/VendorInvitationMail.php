<?php

namespace App\Mail;

use App\Models\VendorUser;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VendorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $vendorName;

    public string $portalName;

    public string $magicLinkUrl;

    public int $linkExpiryHours;

    public function __construct(VendorUser $vendorUser)
    {
        $this->email = $vendorUser->email;
        $this->name = $vendorUser->name;
        $this->vendorName = $vendorUser->vendor->name;
        $this->portalName = setting('vendor_portal.portal_name', 'Vendor Portal');
        $this->linkExpiryHours = (int) setting('vendor_portal.magic_link_expiry_hours', 48);

        // Generate signed magic link URL
        $this->magicLinkUrl = URL::temporarySignedRoute(
            'vendor.magic-login',
            now()->addHours($this->linkExpiryHours),
            ['vendorUser' => $vendorUser->id]
        );
    }

    public function build()
    {
        $viewString = setting('mail.templates.vendor_invitation_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'name' => $this->name,
            'email' => $this->email,
            'vendorName' => $this->vendorName,
            'portalName' => $this->portalName,
            'magicLinkUrl' => $this->magicLinkUrl,
            'linkExpiryHours' => $this->linkExpiryHours,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.vendor_invitation_subject'), [
                'portalName' => $this->portalName,
                'vendorName' => $this->vendorName,
            ]))
            ->html($renderedView);
    }
}
