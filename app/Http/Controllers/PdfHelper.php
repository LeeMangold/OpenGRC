<?php

namespace App\Http\Controllers;

use Exception;
use finfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfHelper extends Controller
{
    public static function getPdfVersion($file): ?string
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 8);
        fclose($handle);

        if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function isPdfEncrypted($file): bool
    {
        \Log::info("Checking if PDF is encrypted: $file");

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }

        $content = fread($handle, 8192); // Read first 8KB
        fclose($handle);

        $isEncrypted = strpos($content, '/Encrypt') !== false;
        \Log::info('PDF encryption status: '.($isEncrypted ? 'Encrypted' : 'Not Encrypted'));

        return $isEncrypted;

    }

    public static function convertPdfTo14($sourceFile, $destFile): bool
    {
        // Convert a PDF to version 1.4 using ghostscript
        $cmd = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile='.escapeshellarg($destFile).' '.escapeshellarg($sourceFile);

        exec($cmd, $output, $returnVar);

        return $returnVar === 0 && file_exists($destFile);
    }

    /**
     * Convert HTML content with image tags to use base64 data URIs for DomPDF compatibility
     * This is necessary because DomPDF cannot access remote URLs or storage paths directly
     */
    public static function convertImagesToBase64($html, $disk = null)
    {
        if (empty($html)) {
            return $html;
        }

        // Use the default storage disk if none specified
        $storageDisk = $disk ?? Storage::disk(config('filesystems.default'));

        // Pattern to match img tags with src attributes
        $pattern = '/<img([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i';

        $html = preg_replace_callback($pattern, function ($matches) use ($storageDisk) {
            $beforeSrc = $matches[1];
            $src = $matches[2];
            $afterSrc = $matches[3];

            // Skip if already base64
            if (strpos($src, 'data:image') === 0) {
                return $matches[0];
            }

            $base64Image = null;

            // Try different methods to get the image content
            try {
                $storagePath = null;

                // Decode HTML entities in the URL (e.g., &amp; to &)
                $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5);

                // Check if it's a remote URL (signed DigitalOcean Spaces URL or other remote image)
                $isRemoteUrl = preg_match('/^https?:\/\//i', $src);

                if ($isRemoteUrl) {
                    // Try to extract storage path from signed URLs (S3, DigitalOcean Spaces, etc.)
                    // These URLs have the format: https://bucket.region.provider.com/path/to/file.png?X-Amz-...
                    // We want to extract the path and access it directly from storage

                    // Parse URL to extract path
                    $parsedUrl = parse_url($src);
                    $urlPath = $parsedUrl['path'] ?? '';

                    // Remove leading slash and extract the storage path
                    $urlPath = ltrim($urlPath, '/');

                    // Check if this looks like a storage path (contains ssp-uploads or other known patterns)
                    if (preg_match('#(ssp-uploads/.+?)(\?|$)#', $urlPath, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                        Log::info('[PdfHelper] Extracted storage path from signed URL', [
                            'url' => $src,
                            'storage_path' => $storagePath,
                        ]);

                        // Try to get from storage disk directly
                        if (self::isSafeStoragePath($storagePath) && $storageDisk->exists($storagePath)) {
                            $imageContent = $storageDisk->get($storagePath);
                            $mimeType = self::detectImageMime($imageContent);
                            $base64Image = $mimeType ? 'data:'.$mimeType.';base64,'.base64_encode($imageContent) : null;

                            Log::info('[PdfHelper] Successfully converted image from storage', [
                                'storage_path' => $storagePath,
                                'mime_type' => $mimeType,
                                'size' => strlen($imageContent),
                            ]);
                        } else {
                            Log::warning('[PdfHelper] Storage path extracted but file not found', [
                                'storage_path' => $storagePath,
                            ]);
                        }
                    }

                    // If we couldn't get it from storage, try downloading (for external images)
                    if (! $base64Image) {
                        Log::info('[PdfHelper] Attempting to download remote image', ['url' => $src]);

                        $base64Image = self::fetchRemoteImage($src);
                    }
                } else {
                    // Handle local storage paths
                    // Parse the source URL to extract the storage path
                    // RichEditor typically stores images with paths like:
                    // /app/priv-storage/ssp-uploads/filename.png
                    // or /storage/ssp-uploads/filename.png

                    // Remove domain if present
                    $cleanSrc = preg_replace('/^https?:\/\/[^\/]+/', '', $src);

                    // Extract the file path after the storage prefix
                    // Handle /app/priv-storage/ prefix (private storage)
                    if (preg_match('#/app/priv-storage/(.+?)(\?.*)?$#', $cleanSrc, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                    }
                    // Handle /storage/ prefix (public storage)
                    elseif (preg_match('#/storage/(.+?)(\?.*)?$#', $cleanSrc, $pathMatches)) {
                        $storagePath = $pathMatches[1];
                    }
                    // Handle direct ssp-uploads/ path
                    elseif (strpos($cleanSrc, 'ssp-uploads/') !== false) {
                        if (preg_match('#(ssp-uploads/[^?]+)#', $cleanSrc, $pathMatches)) {
                            $storagePath = $pathMatches[1];
                        }
                    }

                    // Try to get from storage
                    if ($storagePath && self::isSafeStoragePath($storagePath) && $storageDisk->exists($storagePath)) {
                        $imageContent = $storageDisk->get($storagePath);
                        $mimeType = self::detectImageMime($imageContent);
                        $base64Image = $mimeType ? 'data:'.$mimeType.';base64,'.base64_encode($imageContent) : null;
                    }

                    // Try as public path, confined to the public directory
                    if (! $base64Image) {
                        $publicRoot = realpath(public_path());
                        $publicPath = realpath(public_path(parse_url($cleanSrc, PHP_URL_PATH) ?? ''));
                        if ($publicRoot && $publicPath && str_starts_with($publicPath, $publicRoot.DIRECTORY_SEPARATOR) && is_file($publicPath)) {
                            $imageContent = file_get_contents($publicPath);
                            $mimeType = self::detectImageMime($imageContent);
                            $base64Image = $mimeType ? 'data:'.$mimeType.';base64,'.base64_encode($imageContent) : null;
                        }
                    }
                }

            } catch (Exception $e) {
                Log::warning('[PdfHelper] Failed to convert image to base64', [
                    'src' => $src,
                    'storage_path' => $storagePath ?? 'not found',
                    'error' => $e->getMessage(),
                ]);
            }

            // If we successfully converted the image, use the base64 version
            if ($base64Image) {
                return '<img'.$beforeSrc.'src="'.$base64Image.'"'.$afterSrc.'>';
            }

            // Otherwise, log and return the original tag
            Log::warning('[PdfHelper] Could not convert image to base64', [
                'src' => $src,
                'storage_path' => $storagePath ?? 'not parsed',
            ]);

            return $matches[0];
        }, $html);

        return $html;
    }

    private const MAX_REMOTE_IMAGE_BYTES = 10 * 1024 * 1024;

    /**
     * Download an external image for embedding. Guards against SSRF: http(s) only,
     * no redirects, host must resolve exclusively to public IPs (pinned for the
     * request to prevent DNS rebinding), size-capped, and the payload must be an image.
     */
    private static function fetchRemoteImage(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $host = trim($host, '[]');
        $isIpLiteral = (bool) filter_var($host, FILTER_VALIDATE_IP);
        $ips = $isIpLiteral ? [$host] : array_merge(
            array_column(dns_get_record($host, DNS_A) ?: [], 'ip'),
            array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if ($ips === []) {
            return null;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                Log::warning('[PdfHelper] Refusing to fetch image from non-public address', ['url' => $url, 'ip' => $ip]);

                return null;
            }
        }

        $ch = curl_init($url);

        if (! $isIpLiteral) {
            $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
            $pinnedIp = str_contains($ips[0], ':') ? '['.$ips[0].']' : $ips[0];
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$port}:{$pinnedIp}"]);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_BUFFERSIZE => 16384,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => fn ($ch, $dlTotal, $dlNow) => $dlNow > self::MAX_REMOTE_IMAGE_BYTES ? 1 : 0,
        ]);
        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($imageContent === false || $httpCode !== 200) {
            Log::warning('[PdfHelper] Failed to download remote image', [
                'url' => $url,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
            ]);

            return null;
        }

        $mimeType = self::detectImageMime($imageContent);

        return $mimeType ? 'data:'.$mimeType.';base64,'.base64_encode($imageContent) : null;
    }

    /**
     * Return the image MIME type of the given bytes, or null if they are not a raster image.
     * SVG is excluded since it can carry script and external references.
     */
    private static function detectImageMime(string $content): ?string
    {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

        return in_array($mimeType, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp'], true) ? $mimeType : null;
    }

    private static function isSafeStoragePath(string $path): bool
    {
        return ! str_contains($path, '..') && ! str_contains($path, "\0");
    }

    /**
     * Merge PDF attachments with the main PDF using Ghostscript
     */
    public static function mergePdfs($mainPdfPath, $pdfAttachments, $outputPath, $disk = null)
    {
        try {
            // Collect all PDF files to merge
            $pdfFiles = [];
            $tmpFiles = [];

            // Add the main PDF first
            $pdfFiles[] = escapeshellarg($mainPdfPath);

            // Add each PDF attachment
            if ($disk !== null) {
                $storage = Storage::disk($disk);

                foreach ($pdfAttachments as $attachment) {
                    if ($storage->exists($attachment->file_path)) {
                        // Create a temporary file for the attachment
                        $tmpAttachmentPath = sys_get_temp_dir().'/'.uniqid().'.pdf';
                        file_put_contents($tmpAttachmentPath, $storage->get($attachment->file_path));
                        $tmpFiles[] = $tmpAttachmentPath;

                        // Verify the PDF is valid before adding
                        if (filesize($tmpAttachmentPath) > 0) {
                            $pdfFiles[] = escapeshellarg($tmpAttachmentPath);
                        } else {
                            Log::warning('[PdfHelper] Skipping empty PDF attachment', [
                                'attachment_id' => $attachment->id,
                                'file_name' => $attachment->file_name,
                            ]);
                        }
                    }
                }
            } else {
                // If no disk is specified, assume $pdfAttachments contains file paths
                foreach ($pdfAttachments as $attachmentPath) {
                    if (file_exists($attachmentPath) && filesize($attachmentPath) > 0) {
                        $pdfFiles[] = escapeshellarg($attachmentPath);
                    }
                }
            }

            // Only proceed if we have files to merge
            if (count($pdfFiles) > 1) {
                // Build the Ghostscript command
                $command = sprintf(
                    'gs -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -sOutputFile=%s %s 2>&1',
                    escapeshellarg($outputPath),
                    implode(' ', $pdfFiles)
                );

                // Execute the Ghostscript command
                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);

                // Check if the command was successful
                if ($returnVar !== 0) {
                    Log::error('[PdfHelper] Ghostscript command failed', [
                        'command' => $command,
                        'output' => implode("\n", $output),
                    ]);
                    throw new Exception('Ghostscript command failed: '.implode("\n", $output));
                }

                // Verify the output file was created
                if (! file_exists($outputPath) || filesize($outputPath) == 0) {
                    Log::error('[PdfHelper] Ghostscript failed to create output file', [
                        'output_path' => $outputPath,
                    ]);
                    throw new Exception('Ghostscript failed to create output file');
                }
            } else {
                // If only main PDF exists, just copy it
                copy($mainPdfPath, $outputPath);
            }

            // Clean up temporary files
            foreach ($tmpFiles as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }

            return true;

        } catch (Exception $e) {
            Log::error('[PdfHelper] PDF merging with Ghostscript failed', [
                'main_pdf' => $mainPdfPath,
                'output_path' => $outputPath,
                'error' => $e->getMessage(),
            ]);

            // Clean up temporary files in case of error
            foreach ($tmpFiles ?? [] as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }

            // If merging fails, just copy the main PDF
            copy($mainPdfPath, $outputPath);

            return false;
        }
    }
}
