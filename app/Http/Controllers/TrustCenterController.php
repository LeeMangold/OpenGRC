<?php

namespace App\Http\Controllers;

use App\Enums\AccessRequestStatus;
use App\Mail\TrustCenterAccessRequestMail;
use App\Models\Certification;
use App\Models\TrustCenterAccessRequest;
use App\Models\TrustCenterContentBlock;
use App\Models\TrustCenterDocument;
use App\Models\User;
use App\Notifications\DropdownNotification;
use App\Services\AppLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TrustCenterController extends Controller
{
    /**
     * Display the public Trust Center page.
     */
    public function index()
    {
        // Check if Trust Center is enabled
        if (! setting('trust_center.enabled', true)) {
            abort(404);
        }

        $companyName = setting('trust_center.company_name', '');
        $trustCenterName = setting('trust_center.name', 'Trust Center');

        // Get active certifications with their public documents
        $certifications = Certification::active()
            ->ordered()
            ->with(['documents' => function ($query) {
                $query->active()->ordered();
            }])
            ->get();

        // Get all active documents grouped by trust level
        $publicDocuments = TrustCenterDocument::active()
            ->public()
            ->ordered()
            ->with('certifications')
            ->get();

        $protectedDocuments = TrustCenterDocument::active()
            ->protected()
            ->ordered()
            ->with('certifications')
            ->get();

        // Get enabled content blocks
        $contentBlocks = TrustCenterContentBlock::enabled()
            ->ordered()
            ->get()
            ->keyBy('slug');

        // Get NDA text if required
        $ndaRequired = setting('trust_center.nda_required', true);
        $ndaText = setting('trust_center.nda_text', '');

        return view('trust-center.index', [
            'companyName' => $companyName,
            'trustCenterName' => $trustCenterName,
            'certifications' => $certifications,
            'publicDocuments' => $publicDocuments,
            'protectedDocuments' => $protectedDocuments,
            'contentBlocks' => $contentBlocks,
            'ndaRequired' => $ndaRequired,
            'ndaText' => $ndaText,
        ]);
    }

    /**
     * Download a public document.
     */
    public function downloadPublic(TrustCenterDocument $document)
    {
        // Only allow download of active, public documents
        if (! $document->is_active || ! $document->isPublic()) {
            abort(404);
        }

        $disk = setting('storage.driver', 'private');
        $storage = Storage::disk($disk);

        if (! $storage->exists($document->file_path)) {
            abort(404);
        }

        return $storage->download($document->file_path, $document->file_name);
    }

    /**
     * Handle access request submission.
     */
    public function requestAccess(Request $request)
    {
        // Honeypot check - if this field is filled, it's likely a bot
        if ($request->filled('website_url')) {
            // Log the bot attempt but return success to not reveal detection
            AppLogger::warning(
                category: 'trust_center',
                event: 'BotDetected',
                message: 'Honeypot triggered on Trust Center access request',
                context: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Return fake success to not reveal bot detection
            return back()->with('success', __('Your access request has been submitted. You will receive an email when it is reviewed.'));
        }

        $validator = Validator::make($request->all(), [
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
            'requester_company' => 'required|string|max:255',
            'reason' => 'nullable|string|max:5000',
            'nda_agreed' => 'required|accepted',
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:trust_center_documents,id',
            'website_url' => 'max:0', // Honeypot must be empty
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verify all requested documents are protected and active
        $documents = TrustCenterDocument::active()
            ->protected()
            ->whereIn('id', $request->document_ids)
            ->get();

        if ($documents->isEmpty()) {
            return back()
                ->with('error', __('No valid documents selected.'))
                ->withInput();
        }

        // Create the access request
        $accessRequest = TrustCenterAccessRequest::create([
            'requester_name' => $request->requester_name,
            'requester_email' => $request->requester_email,
            'requester_company' => $request->requester_company,
            'reason' => $request->reason,
            'nda_agreed' => true,
            'status' => AccessRequestStatus::PENDING,
        ]);

        // Attach the requested documents
        $accessRequest->documents()->attach($documents->pluck('id'));

        // Log the access request
        AppLogger::info(
            category: 'trust_center',
            event: 'AccessRequest',
            message: 'Trust Center access request submitted',
            context: [
                'access_request_id' => $accessRequest->id,
                'requester_name' => $accessRequest->requester_name,
                'requester_email' => $accessRequest->requester_email,
                'requester_company' => $accessRequest->requester_company,
                'document_count' => $documents->count(),
                'document_ids' => $documents->pluck('id')->toArray(),
            ],
            subject: $accessRequest
        );

        // Send notification email to Trust Center managers
        $this->notifyManagers($accessRequest);

        return back()->with('success', __('Your access request has been submitted. You will receive an email when it is reviewed.'));
    }

    /**
     * Access protected documents via magic link.
     */
    public function protectedAccess(Request $request, TrustCenterAccessRequest $accessRequest)
    {
        // Validate the signed URL (middleware handles this)
        // Check if access is still valid
        if (! $accessRequest->isAccessValid()) {
            // Log expired access attempt
            AppLogger::warning(
                category: 'trust_center',
                event: 'MagicLinkExpired',
                message: 'Expired magic link access attempted',
                context: [
                    'access_request_id' => $accessRequest->id,
                    'requester_email' => $accessRequest->requester_email,
                    'expired_at' => $accessRequest->access_expires_at?->toDateTimeString(),
                ],
                subject: $accessRequest
            );

            return view('trust-center.access-expired');
        }

        // Record the access
        $accessRequest->recordAccess();

        // Get the approved documents
        $documents = $accessRequest->documents()
            ->where('is_active', true)
            ->get();

        // Log successful magic link access
        AppLogger::info(
            category: 'trust_center',
            event: 'MagicLinkAccess',
            message: 'Magic link accessed successfully',
            context: [
                'access_request_id' => $accessRequest->id,
                'requester_email' => $accessRequest->requester_email,
                'access_count' => $accessRequest->access_count,
                'expires_at' => $accessRequest->access_expires_at?->toDateTimeString(),
            ],
            subject: $accessRequest
        );

        return view('trust-center.protected-access', [
            'accessRequest' => $accessRequest,
            'documents' => $documents,
        ]);
    }

    /**
     * Download a protected document via magic link.
     */
    public function downloadProtected(Request $request, TrustCenterAccessRequest $accessRequest, TrustCenterDocument $document)
    {
        // Check if access is still valid
        if (! $accessRequest->isAccessValid()) {
            abort(403, __('Access has expired.'));
        }

        // Check if the document is in the approved list
        if (! $accessRequest->documents()->where('trust_center_documents.id', $document->id)->exists()) {
            abort(403, __('Access not authorized for this document.'));
        }

        // Check if document is still active
        if (! $document->is_active) {
            abort(404);
        }

        $disk = setting('storage.driver', 'private');
        $storage = Storage::disk($disk);

        if (! $storage->exists($document->file_path)) {
            abort(404);
        }

        // Record the download
        $accessRequest->documents()->updateExistingPivot($document->id, [
            'downloaded_at' => now(),
        ]);

        return $storage->download($document->file_path, $document->file_name);
    }

    /**
     * Notify Trust Center managers about a new access request.
     */
    protected function notifyManagers(TrustCenterAccessRequest $accessRequest): void
    {
        // Get users with 'Manage Trust Access' permission
        $managers = User::permission('Manage Trust Access')->get();

        foreach ($managers as $manager) {
            try {
                // Send email notification
                Mail::to($manager->email)->send(new TrustCenterAccessRequestMail($accessRequest));
            } catch (\Exception $e) {
                // Log but don't fail
                \Log::warning('Failed to send Trust Center access request email', [
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                // Send in-app notification
                $manager->notify(new DropdownNotification(
                    title: __('New Trust Center Access Request'),
                    body: __(':name from :company has requested access to protected documents.', [
                        'name' => $accessRequest->requester_name,
                        'company' => $accessRequest->requester_company,
                    ]),
                    icon: 'heroicon-o-inbox',
                    color: 'warning',
                    actionUrl: route('filament.app.resources.trust-center-access-requests.view', ['record' => $accessRequest->id]),
                    actionLabel: __('Review Request')
                ));
            } catch (\Exception $e) {
                \Log::warning('Failed to send Trust Center access request notification', [
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
