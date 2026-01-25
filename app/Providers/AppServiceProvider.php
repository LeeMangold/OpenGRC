<?php

namespace App\Providers;

use App\Livewire\CustomSessionGuard;
use App\Models\User;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use BladeUI\Icons\Factory as IconFactory;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override the package's SessionGuard component with our custom one
        Livewire::component('filament-inactivity-guard::session-guard', CustomSessionGuard::class);

        // Disable mass assignment protection
        Model::unguard();

        // Only skip the install check if running the installer command or actual PHPUnit tests
        $isInstaller = false;
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            if (isset($argv[1]) && (
                $argv[1] === 'opengrc:install'
            || $argv[1] === 'opengrc:deploy'
            || $argv[1] === 'package:discover'
            || $argv[1] === 'filament:upgrade'
            || $argv[1] === 'vendor:publish'
            || $argv[1] === 'test'
            || $argv[1] === 'key:generate'
            || $argv[1] === 'migrate'
            || $argv[1] === 'migrate:fresh'
            || $argv[1] === 'migrate:refresh'
            || $argv[1] === 'migrate:reset'
            || $argv[1] === 'migrate:rollback'
            || $argv[1] === 'migrate:status'
            )) {
                $isInstaller = true;
            }
        }

        // Skip settings config only when running actual PHPUnit tests (not just APP_ENV=testing)
        if ($this->app->runningUnitTests()) {
            $isInstaller = true;
        }

        if (! $isInstaller) {
            if (Schema::hasTable('settings')) {

                Config::set('app.name', setting('general.name', 'OpenGRC'));
                Config::set('app.url', setting('general.url', 'https://opengrc.test'));

                // Decrypt mail password if it's encrypted
                $mailPassword = setting('mail.password');
                if (! empty($mailPassword)) {
                    try {
                        $mailPassword = Crypt::decryptString($mailPassword);
                    } catch (\Exception $e) {
                        // If decryption fails, assume it's plaintext (legacy data)
                    }
                }

                config()->set('mail', array_merge(config('mail'), [
                    'driver' => 'smtp',
                    'transport' => 'smtp',
                    'host' => setting('mail.host'),
                    'username' => setting('mail.username'),
                    'password' => $mailPassword,
                    'encryption' => setting('mail.encryption'),
                    'port' => setting('mail.port'),
                    'from' => [
                        'address' => setting('mail.from'),
                        'name' => setting('general.name'),
                    ],
                ]));

                // Configure filesystem based on settings
                $storageDriver = setting('storage.driver', 'private');

                // Ensure local disk is always configured
                config()->set('filesystems.disks.local', array_merge(config('filesystems.disks.local', []), [
                    'driver' => 'local',
                    'root' => storage_path('app'),
                    'throw' => false,
                ]));

                // Configure S3-compatible storage (AWS S3 or DigitalOcean Spaces)
                if (in_array($storageDriver, ['s3', 'digitalocean'])) {
                    $settingKey = "storage.{$storageDriver}";
                    $accessKey = setting("{$settingKey}.key");
                    $secretKey = setting("{$settingKey}.secret");
                    $region = setting("{$settingKey}.region", $storageDriver === 's3' ? 'us-east-1' : 'nyc3');
                    $bucket = setting("{$settingKey}.bucket");

                    try {
                        // Decrypt credentials if they exist and are encrypted
                        if (! empty($accessKey)) {
                            $accessKey = Crypt::decryptString($accessKey);
                        }
                        if (! empty($secretKey)) {
                            $secretKey = Crypt::decryptString($secretKey);
                        }

                        $diskConfig = [
                            'driver' => 's3',
                            'key' => $accessKey,
                            'secret' => $secretKey,
                            'bucket' => $bucket,
                        ];

                        if ($storageDriver === 'digitalocean') {
                            // DigitalOcean Spaces uses path-style endpoint
                            $diskConfig['region'] = 'us-east-1'; // Always us-east-1 for AWS SDK compatibility
                            $diskConfig['endpoint'] = 'https://'.strtolower($region).'.digitaloceanspaces.com';
                            $diskConfig['use_path_style_endpoint'] = true;
                        } else {
                            // AWS S3
                            $diskConfig['region'] = $region;
                            $diskConfig['use_path_style_endpoint'] = false;
                        }

                        config()->set("filesystems.disks.{$storageDriver}", array_merge(
                            config("filesystems.disks.{$storageDriver}", []),
                            $diskConfig
                        ));
                    } catch (\Exception $e) {
                        \Log::error("Failed to decrypt {$storageDriver} credentials: ".$e->getMessage());
                        $storageDriver = 'private';
                    }
                }

                // Set the default filesystem driver
                config()->set('filesystems.default', $storageDriver);

                // Set session lifetime from settings
                Config::set('session.lifetime', setting('security.session_timeout', 15));
            } else {
                // if table "settings" does not exist
                // Error that app was not installed properly
                abort(500, 'OpenGRC was not installed properly. Please review the
                installation guide at https://docs.opengrc.com to install the app.');
            }
        }

        Gate::before(function ($user, string $ability) {
            // Only apply super admin bypass for regular User model, not VendorUser
            if ($user instanceof User && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['en', 'es', 'fr', 'hr']);
        });

        Table::configureUsing(function (Table $table): Table {
            return $table->paginationPageOptions([10, 25, 50, 100]);
        });

        FilamentColor::register([
            'bg-grcblue' => [
                50 => '#eaf3f7',
                100 => '#d4e7ef',
                200 => '#a9cfe0',
                300 => '#7eb7d1',
                400 => '#1375a0',
                500 => '#106689',
                600 => '#0d5773',
                700 => '#0a485d',
                800 => '#374151',
                900 => '#212a3a',
            ],
            'danger' => [
                50 => '254, 242, 242',
                100 => '254, 226, 226',
                200 => '254, 202, 202',
                300 => '252, 165, 165',
                400 => '248, 113, 113',
                500 => '239, 68, 68',
                600 => '220, 38, 38',
                700 => '185, 28, 28',
                800 => '153, 27, 27',
                900 => '127, 29, 29',
                950 => '69, 10, 10',
            ],
        ]);

    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Force HTTPS in production environments (must be in register, not boot)
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');

            // Ensure HTTPS is detected from proxy headers
            $this->app['request']->server->set('HTTPS', 'on');
            $_SERVER['HTTPS'] = 'on';
        }

        // Register custom icons
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory) {
            $factory->add('grc', [
                'path' => resource_path('svg'),
                'prefix' => 'grc',
            ]);
        });
    }
}
