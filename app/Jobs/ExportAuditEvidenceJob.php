<?php

namespace App\Jobs;

use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportAuditEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auditId;

    /**
     * Create a new job instance.
     */
    public function __construct($auditId)
    {
        $this->auditId = $auditId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $audit = Audit::with([
            'auditItems',
            'auditItems.dataRequests.responses.attachments',
            'auditItems.auditable'
        ])->findOrFail($this->auditId);
        Log::info('*** CHECKPOINT 1 ***');
        $exportPath = storage_path("app/exports/audit_{$this->auditId}/");
        if (!Storage::exists("app/exports/audit_{$this->auditId}/")) {
            Storage::makeDirectory("app/exports/audit_{$this->auditId}/");
        }

        
        
        $pdfFiles = [];
        // Gather all Data Requests for this audit, but only those that exist
        $dataRequests = $audit->auditItems->flatMap(function ($item) {
            return $item->dataRequests;
        })->filter(); // filter out empty/null

        foreach ($dataRequests as $dataRequest) {
            $auditItem = $dataRequest->auditItem;
            // Ensure responses and attachments are loaded for this dataRequest
            $dataRequest->loadMissing(['responses.attachments']);
            
            // Preprocess attachments: add base64_image property for images
            $disk = setting('filesystems.default', 'private');
            foreach ($dataRequest->responses as $response) {
                
                foreach ($response->attachments as $attachment) {
                    $isImage = false;
                    $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                    $imageExts = ['jpg','jpeg','png','gif','bmp','webp'];
                    if (in_array($ext, $imageExts)) {
                        $isImage = true;
                    }
                    $storage = \Storage::disk($disk);
                    $attachment->base64_image = null;                    
                    \Log::debug('[ExportAuditEvidenceJob] Processing attachment', [
                        'attachment_id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_path' => $attachment->file_path,
                        'is_image' => $isImage,
                        'disk' => $disk,
                    ]);
                    if ($isImage) {
                        $exists = $storage->exists($attachment->file_path);
                        \Log::info('[ExportAuditEvidenceJob] Image file existence', [
                            'file_path' => $attachment->file_path,
                            'exists' => $exists
                        ]);
                        if ($exists) {
                            $imgRaw = $storage->get($attachment->file_path);
                            Log::info($imgRaw);
                            $mime = $storage->mimeType($attachment->file_path);
                            \Log::info('[ExportAuditEvidenceJob] Image file details', [
                                'file_path' => $attachment->file_path,
                                'mime' => $mime,
                                'raw_size' => strlen($imgRaw)
                            ]);
                            $attachment->base64_image = 'data:' . $mime . ';base64,' . base64_encode($imgRaw);
                            \Log::debug('[ExportAuditEvidenceJob] base64_image set', [
                                'attachment_id' => $attachment->id,
                                'base64_set' => $attachment->base64_image ? true : false
                            ]);
                        }
                    }
                }
            }

            $pdf = Pdf::loadView('pdf.audit-item', [
                'audit' => $audit,
                'auditItem' => $auditItem,
                'dataRequest' => $dataRequest,
            ]);
            $filename = "data_request_{$dataRequest->id}.pdf";
            $pdf->save($exportPath . $filename);
            $pdfFiles[] = $exportPath . $filename;
        }
        // Create ZIP
        $zipPath = $exportPath . "audit_{$this->auditId}_data_requests.zip";
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($pdfFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }


        // Optionally, clean up individual PDFs if you only want to keep the ZIP
        foreach ($pdfFiles as $file) {
            unlink($file);
        }

    }
}
