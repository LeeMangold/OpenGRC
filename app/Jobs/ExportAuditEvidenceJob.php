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
use Illuminate\Support\Facades\Schema;
use App\Models\FileAttachment;

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

        $exportPath = storage_path("app/exports/audit_{$this->auditId}/");
        if (!Storage::exists("app/exports/audit_{$this->auditId}/") && !Storage::disk('s3')) {
            Storage::makeDirectory("app/exports/audit_{$this->auditId}/");
        }
        
        $disk = setting('storage.driver', 'private');
        $pdfFiles = [];
        $dataRequests = $audit->auditItems->flatMap(function ($item) {
            return $item->dataRequests;
        })->filter();

        // Directory/key prefix for exports
        $exportDir = "exports/audit_{$this->auditId}/";

        // Create a local temp directory for PDFs
        $tmpDir = sys_get_temp_dir() . "/audit_{$this->auditId}_" . uniqid();
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        $localFiles = [];

        foreach ($dataRequests as $dataRequest) {
            $auditItem = $dataRequest->auditItem;
            $dataRequest->loadMissing(['responses.attachments']);
            
            // Preprocess attachments: add base64_image property for images
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
                    if ($isImage) {
                        $exists = $storage->exists($attachment->file_path);
                        \Log::info('[ExportAuditEvidenceJob] Image file existence', [
                            'file_path' => $attachment->file_path,
                            'exists' => $exists
                        ]);
                        if ($exists) {
                            $imgRaw = $storage->get($attachment->file_path);
                            $mime = $storage->mimeType($attachment->file_path);
                            $attachment->base64_image = 'data:' . $mime . ';base64,' . base64_encode($imgRaw);
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
            $localPath = $tmpDir . '/' . $filename;
            $pdf->save($localPath);
            $localFiles[] = $localPath;
            $pdfFiles[] = $filename;
        }

        if ($disk === 's3') {
            // Create ZIP locally
            $zipLocalPath = $tmpDir . "/audit_{$this->auditId}_data_requests.zip";
            $zip = new ZipArchive;
            if ($zip->open($zipLocalPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($localFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }
            // Upload ZIP to S3
            $zipS3Path = $exportDir . "audit_{$this->auditId}_data_requests.zip";
            \Storage::disk('s3')->put($zipS3Path, file_get_contents($zipLocalPath));

            // Create or update FileAttachment for the ZIP
            FileAttachment::updateOrCreate(
                [
                    'audit_id' => $this->auditId,
                    'data_request_response_id' => null,
                    'file_name' => "audit_{$this->auditId}_data_requests.zip",
                ],
                [
                    'file_path' => $zipS3Path,
                    'file_size' => filesize($zipLocalPath),
                    'uploaded_by' => auth()->id() ?? null,
                    'description' => 'Exported audit evidence ZIP',
                ]
            );
            // Clean up
            // Remove all files in the temp directory
            $files = glob($tmpDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tmpDir);
        } else {
            // Local disk: create ZIP directly in export dir
            $exportPath = storage_path('app/private/' . $exportDir);
            if (!is_dir($exportPath)) {
                mkdir($exportPath, 0777, true);
            }
            $zipPath = $exportPath . "audit_{$this->auditId}_data_requests.zip";
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($localFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Create or update FileAttachment for the ZIP
            FileAttachment::updateOrCreate(
                [
                    'audit_id' => $this->auditId,
                    'data_request_response_id' => null,
                    'file_name' => "audit_{$this->auditId}_data_requests.zip",
                ],
                [
                    'file_path' => $exportDir . "audit_{$this->auditId}_data_requests.zip",
                    'file_size' => filesize($zipPath),
                    'uploaded_by' => auth()->id() ?? null,
                    'description' => 'Exported audit evidence ZIP',
                ]
            );

            // Remove all files in the temp directory
            $files = glob($tmpDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tmpDir);
        }
    }
}
