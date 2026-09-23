<?php

namespace App\Mail;

use App\Models\VendorDocument;
use App\Models\VendorUser;
use App\Services\MailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorDocumentExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $vendorName;

    public string $portalName;

    public string $documentName;

    public string $documentType;

    public string $expirationDate;

    public int $daysUntilExpiration;

    public string $portalUrl;

    public function __construct(VendorDocument $document, VendorUser $vendorUser)
    {
        $this->email = $vendorUser->email;
        $this->name = $vendorUser->name;
        $this->vendorName = $vendorUser->vendor->name;
        $this->portalName = setting('vendor_portal.portal_name', 'Vendor Portal');
        $this->documentName = $document->name;
        $this->documentType = $document->document_type->getLabel();
        $this->expirationDate = $document->expiration_date->format('F j, Y');
        $this->daysUntilExpiration = $document->daysUntilExpiration() ?? 0;
        $this->portalUrl = url('/vendor/documents');
    }

    public function build()
    {
        $viewString = setting('mail.templates.vendor_document_expiring_body');

        $renderedView = MailTemplateRenderer::html($viewString, [
            'name' => $this->name,
            'email' => $this->email,
            'vendorName' => $this->vendorName,
            'portalName' => $this->portalName,
            'documentName' => $this->documentName,
            'documentTitle' => $this->documentName,
            'documentType' => $this->documentType,
            'expirationDate' => $this->expirationDate,
            'daysUntilExpiration' => $this->daysUntilExpiration,
            'daysRemaining' => $this->daysUntilExpiration,
            'portalUrl' => $this->portalUrl,
        ]);

        return $this->from(setting('mail.from'))
            ->to($this->email)
            ->subject(MailTemplateRenderer::text(setting('mail.templates.vendor_document_expiring_subject'), [
                'documentName' => $this->documentName,
                'documentTitle' => $this->documentName,
                'daysUntilExpiration' => $this->daysUntilExpiration,
                'daysRemaining' => $this->daysUntilExpiration,
            ]))
            ->html($renderedView);
    }
}
