<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Standard;
use Exception;
use Filament\Notifications\Notification;
use Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Schema;
use Storage;

class BundleController extends Controller
{
    public static function generate($code): array
    {
        try {
            $standard = Standard::where('code', $code)->with('controls')->firstOrFail();

            // Build bundle structure with controls that include optional fields if they exist
            $bundleData = [
                'code' => $standard->code,
                'name' => $standard->name,
                'authority' => $standard->authority,
                'description' => $standard->description,
                'controls' => $standard->controls->map(function ($control) {
                    $controlData = [
                        'code' => $control->code,
                        'title' => $control->title,
                        'description' => $control->description,
                        'discussion' => $control->discussion,
                        'test' => $control->test,
                        'type' => $control->type,
                        'category' => $control->category,
                        'enforcement' => $control->enforcement,
                    ];

                    // Include optional fields if they have values
                    if (! empty($control->audit_sanction_date)) {
                        $controlData['audit_sanction_date'] = $control->audit_sanction_date;
                    }
                    if (! empty($control->priority)) {
                        $controlData['priority'] = $control->priority;
                    }

                    return $controlData;
                })->toArray(),
            ];

            $filePath = 'bundlegen/'.$code.'.json';
            Storage::disk('private')->put($filePath, json_encode($bundleData, JSON_PRETTY_PRINT));

            return ['success' => 'Bundle generated successfully! Saved to storage/app/private/'.$filePath];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function retrieve(): void
    {
        $repo = setting('general.repo', 'https://repo.opengrc.com');

        try {
            $response = Http::withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ])->get($repo)->throw();
            $bundles = json_decode($response->body());

            foreach ($bundles as $bundle) {
                Bundle::updateOrCreate(
                    ['code' => $bundle->code],
                    [
                        'code' => $bundle->code,
                        'name' => $bundle->name,
                        'version' => $bundle->version,
                        'authority' => $bundle->authority,
                        'description' => $bundle->description,
                        'repo_url' => $bundle->url,
                        'type' => $bundle->type ?? 'Standard',
                    ]
                );
            }

        } catch (RequestException $e) {
            // Catch exceptions such as 4xx/5xx HTTP status codes or connection issues
            Notification::make()
                ->title('Error Updating Repository')
                ->body($e->getMessage())
                ->color('danger')
                ->send();
        } catch (\Exception $e) {
            // Catch any other potential exceptions
            Notification::make()
                ->title('Error Updating Repository')
                ->body($e->getMessage())
                ->color('danger')
                ->send();
        }

        Notification::make()
            ->title('Repository Updated')
            ->body('Latest Repository content has been retrieved successfully!')
            ->send();
    }

    public static function importBundle(Bundle $bundle): void
    {
        \Log::info('Importing bundle: '.$bundle->code);

        try {
            $response = Http::get($bundle->repo_url)->throw();

            // GitHub raw URLs return application/octet-stream, so we need to force decode
            // Clean invalid UTF-8 sequences that might break JSON parsing
            $body = mb_convert_encoding($response->body(), 'UTF-8', 'UTF-8');
            $bundle_content = json_decode($body, true);

            // Debug: Check if JSON parsing failed
            if ($bundle_content === null) {
                \Log::error('JSON decode failed', [
                    'url' => $bundle->repo_url,
                    'status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'body_preview' => substr($response->body(), 0, 500),
                ]);
                throw new \Exception('Failed to decode JSON response from: '.$bundle->repo_url);
            }

            // Validate required fields exist
            if (! isset($bundle_content['code']) || ! isset($bundle_content['controls'])) {
                \Log::error('Invalid bundle structure', [
                    'url' => $bundle->repo_url,
                    'keys' => array_keys($bundle_content),
                ]);
                throw new \Exception('Bundle JSON is missing required fields (code or controls)');
            }

            $standard = Standard::updateOrCreate(
                ['code' => $bundle->code],
                [
                    'code' => $bundle_content['code'],
                    'name' => $bundle_content['name'],
                    'authority' => $bundle_content['authority'],
                    'description' => $bundle_content['description'],
                ]
            );

            \Log::info('Importing bundle: '.$bundle->code);

            // Check if the optional columns exist in the database
            $hasAuditSanctionDate = Schema::hasColumn('controls', 'audit_sanction_date');
            $hasPriority = Schema::hasColumn('controls', 'priority');

            foreach ($bundle_content['controls'] as $control) {

                $controlData = [
                    'title' => $control['title'],
                    'code' => $control['code'],
                    'description' => $control['description'],
                    'discussion' => $control['discussion'] ?? null,
                    'test' => $control['test'] ?? null,
                    'type' => $control['type'],
                    'category' => $control['category'],
                    'enforcement' => $control['enforcement'],
                ];

                // Only add these fields if the columns exist in the database AND the bundle provides them
                if ($hasAuditSanctionDate && isset($control['audit_sanction_date'])) {
                    $controlData['audit_sanction_date'] = $control['audit_sanction_date'];
                }
                if ($hasPriority && isset($control['priority'])) {
                    $controlData['priority'] = $control['priority'];
                }

                $standard->controls()->updateOrCreate(
                    ['code' => $control['code']],
                    $controlData
                );
            }

            $bundle->update(['status' => 'imported']);

        } catch (RequestException $e) {
            // Catch exceptions such as 4xx/5xx HTTP status codes or connection issues
            \Log::error('Bundle download failed', [
                'bundle' => $bundle->code,
                'url' => $bundle->repo_url,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Bundle Import Failed')
                ->body('Download failed: '.$e->getMessage())
                ->color('danger')
                ->send();

            return;
        } catch (\Exception $e) {
            // Catch any other potential exceptions
            \Log::error('Bundle import error', [
                'bundle' => $bundle->code,
                'url' => $bundle->repo_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Bundle Import Failed')
                ->body('An unexpected error occurred: '.$e->getMessage())
                ->color('danger')
                ->send();

            return;
        }

        Notification::make()
            ->title('Repository Updated')
            ->body('Latest Repository content has been retrieved successfully!')
            ->send();

    }
}
