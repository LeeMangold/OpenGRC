<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Standard;
use Exception;
use Filament\Notifications\Notification;
use Http;
use Illuminate\Http\Client\RequestException;
use Storage;

class BundleController extends Controller
{
    public static function generate($code): array
    {
        try {
            $standard = Standard::where('code', $code)->with('controls')->firstOrFail();
            $filePath = 'bundlegen/'.$code.'.json';
            Storage::disk('private')->put($filePath, json_encode($standard));

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

        try {
            // Using throw() will cause an exception if the response is not a successful (2xx) status
            $response = Http::get($bundle->repo_url)->throw();
            $bundle_content = json_decode($response->body());

            $standard = Standard::updateOrCreate(
                ['code' => $bundle->code],
                [
                    'code' => $bundle_content->code,
                    'name' => $bundle_content->name,
                    'authority' => $bundle_content->authority,
                    'description' => $bundle_content->description,
                ]
            );

            foreach ($bundle_content->controls as $control) {
                $standard->controls()->updateOrCreate(
                    ['code' => $control->code],
                    [

                        'title' => $control->title,
                        'code' => $control->code,
                        'description' => $control->description,
                        'discussion' => $control->discussion,
                        'test' => $control->test,
                        'type' => $control->type,
                        'category' => $control->category,
                        'enforcement' => $control->enforcement,
                    ]
                );
            }

            $bundle->update(['status' => 'imported']);

        } catch (RequestException $e) {
            // Catch exceptions such as 4xx/5xx HTTP status codes or connection issues
            dd('Download failed: '.$e->getMessage());
        } catch (\Exception $e) {
            // Catch any other potential exceptions
            dd('An unexpected error occurred: '.$e->getMessage());
        }

        Notification::make()
            ->title('Repository Updated')
            ->body('Latest Repository content has been retrieved successfully!')
            ->send();

    }
}
