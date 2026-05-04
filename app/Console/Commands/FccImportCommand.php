<?php

namespace App\Console\Commands;

use App\Models\FccFacility;
use App\Models\FccLicense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pull live FCC broadcast license data from public sources.
 *
 * Sources:
 *   - LMS public report API:  https://enterpriseefiling.fcc.gov/dataentry/public/tv/publicFacilitySearch.html
 *   - CDBS daily dumps:        https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/
 *
 * This command is intentionally conservative: it imports a small set of
 * stations specified by --calls=, looking each up via the FCC public
 * facility-by-call-sign endpoint. For a full ULS / LMS bulk sync use
 * the CDBS daily dump tables (am_eng_data, fm_eng_data, tv_eng_data,
 * facility, application, license_filing) loaded into a separate import
 * pipeline.
 */
class FccImportCommand extends Command
{
    protected $signature = 'fcc:import
                            {--calls= : Comma-separated call signs to import (e.g. WABC,WTOP,KCBS-FM)}
                            {--dry-run : Print what would be imported without writing}';

    protected $description = 'Import live FCC broadcast license data for given call signs';

    public function handle(): int
    {
        $calls = collect(explode(',', (string) $this->option('calls')))
            ->map(fn ($c) => strtoupper(trim($c)))
            ->filter()
            ->unique();

        if ($calls->isEmpty()) {
            $this->error('Specify --calls=WABC,WTOP,...');
            $this->line('');
            $this->line('Example:');
            $this->line('  php artisan fcc:import --calls=WABC,WTOP,KCBS-FM,WGBH-FM');
            return self::INVALID;
        }

        $this->info('Pulling FCC public-facility data for '.$calls->count().' station(s)…');

        foreach ($calls as $call) {
            try {
                $data = $this->fetchFccPublicFacility($call);
                if (! $data) {
                    $this->warn("  ✗ {$call}: not found in FCC LMS");
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("  · {$call}: {$data['licensee']} ({$data['service']} {$data['frequency']}) — facility {$data['facility_id']}");
                    continue;
                }

                $facility = FccFacility::updateOrCreate(
                    ['facility_id' => $data['facility_id']],
                    [
                        'name' => $data['facility_name'] ?? $call.' Facility',
                        'community_of_license' => $data['community'],
                        'state' => $data['state'],
                        'owner' => $data['licensee'],
                    ]
                );

                FccLicense::updateOrCreate(
                    ['call_sign' => $call],
                    [
                        'frn' => $data['frn'] ?? '',
                        'licensee' => $data['licensee'],
                        'service' => $data['service'],
                        'channel_or_frequency' => $data['frequency'],
                        'expiration_date' => $data['expiration_date'] ?? null,
                        'status' => 'active',
                        'compliance_score' => 100.0,
                        'facility_id' => $facility->id,
                    ]
                );

                $this->info("  ✓ {$call}: {$data['licensee']}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$call}: ".$e->getMessage());
            }
        }

        $this->line('');
        $this->info('Done. Run `php artisan db:seed --class=FccOperationalSeeder` to add EAS/Issues-Programs/Public-File demo data for the imported stations.');

        return self::SUCCESS;
    }

    /**
     * Fetch one station's public facility record from the FCC LMS.
     * Returns a normalized array or null if the station isn't found.
     *
     * Note: the LMS public-facility endpoint URL and response shape may
     * change. If you need a hardened pull, drop in a CDBS bulk-data
     * loader (the `facility`, `am/fm/tv_eng_data`, and `license_filing`
     * tables from https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/).
     */
    protected function fetchFccPublicFacility(string $call): ?array
    {
        $url = 'https://publicfiles.fcc.gov/api/manager/station/search/'.urlencode($call).'.json';

        $response = Http::timeout(10)->acceptJson()->get($url);
        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();
        $hits = data_get($payload, 'stations', []);
        if (empty($hits)) {
            return null;
        }

        $h = $hits[0];

        return [
            'facility_id'    => (string) data_get($h, 'facilityId'),
            'facility_name'  => data_get($h, 'transmitterName') ?? null,
            'frn'            => data_get($h, 'frn') ?? '',
            'licensee'       => data_get($h, 'licensee') ?? data_get($h, 'entityName', 'Unknown'),
            'service'        => $this->mapService(data_get($h, 'serviceCode') ?? data_get($h, 'service', 'OTHER')),
            'frequency'      => data_get($h, 'frequency') ?? data_get($h, 'channel', ''),
            'community'      => data_get($h, 'community.city') ?? data_get($h, 'communityCity'),
            'state'          => data_get($h, 'community.state') ?? data_get($h, 'communityState'),
            'expiration_date'=> data_get($h, 'licenseExpirationDate'),
        ];
    }

    protected function mapService(string $code): string
    {
        $code = strtoupper(trim($code));
        return match (true) {
            str_starts_with($code, 'AM') => 'AM',
            str_starts_with($code, 'FM'), $code === 'FX' => 'FM',
            str_starts_with($code, 'TV'), $code === 'DT', $code === 'DC' => 'TV',
            $code === 'LPFM' => 'LPFM',
            $code === 'LPTV', $code === 'TX' => 'LPTV',
            default => 'OTHER',
        };
    }
}
