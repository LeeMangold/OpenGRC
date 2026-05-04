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
     * Fetch one station's public facility record from the FCC.
     *
     * The FCC's public-search endpoints have shifted around over time.
     * We try multiple known-good URL shapes in order. If you're hitting
     * this in production and all fall through, the canonical fallback is
     * a CDBS bulk-data loader (https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/).
     */
    protected function fetchFccPublicFacility(string $call): ?array
    {
        $candidates = [
            // 1. Public Inspection Files manager — search by callsign query string
            'https://publicfiles.fcc.gov/api/manager/station/search?searchString='.urlencode($call),

            // 2. Same site, callsign as path segment
            'https://publicfiles.fcc.gov/api/manager/station/'.urlencode($call),

            // 3. LMS public-facility elastic search
            'https://enterpriseefiling.fcc.gov/dataentry/api/elasticsearch/public/api/public/facility/search?callSign='.urlencode($call),
        ];

        foreach ($candidates as $url) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'OpenGRC-FCC-Compliance/1.0'])
                    ->acceptJson()
                    ->get($url);

                if (! $response->ok()) {
                    continue;
                }

                $payload = $response->json();
                if (! is_array($payload) || empty($payload)) {
                    continue;
                }

                $hit = $this->extractFirstStation($payload);
                if (! $hit) {
                    continue;
                }

                $facilityId = (string) data_get($hit, 'facilityId') ?: (string) data_get($hit, 'facility_id') ?: (string) data_get($hit, 'id');
                if (! $facilityId) {
                    continue;
                }

                return [
                    'facility_id'     => $facilityId,
                    'facility_name'   => data_get($hit, 'transmitterName') ?? data_get($hit, 'facilityName') ?? null,
                    'frn'             => (string) (data_get($hit, 'frn') ?? data_get($hit, 'licenseeFrn') ?? ''),
                    'licensee'        => data_get($hit, 'licensee') ?? data_get($hit, 'entityName') ?? data_get($hit, 'licenseeName') ?? 'Unknown',
                    'service'         => $this->mapService((string) (data_get($hit, 'serviceCode') ?? data_get($hit, 'service') ?? 'OTHER')),
                    'frequency'       => (string) (data_get($hit, 'frequency') ?? data_get($hit, 'channel') ?? ''),
                    'community'       => data_get($hit, 'community.city') ?? data_get($hit, 'communityCity') ?? data_get($hit, 'communityServed.city'),
                    'state'           => data_get($hit, 'community.state') ?? data_get($hit, 'communityState') ?? data_get($hit, 'communityServed.state'),
                    'expiration_date' => data_get($hit, 'licenseExpirationDate') ?? data_get($hit, 'expirationDate'),
                    '_source_url'     => $url,
                ];
            } catch (\Throwable $e) {
                // try next URL
                continue;
            }
        }

        return null;
    }

    /**
     * Extract the first station-like record from a heterogeneous payload.
     */
    protected function extractFirstStation(array $payload): ?array
    {
        // Common shapes:
        //  - { stations: [ {...} ] }
        //  - { hits: [ {...} ] }
        //  - { data: [ {...} ] }
        //  - [ {...} ]            (bare array)
        //  - { facilityId: ... }  (single record)
        foreach (['stations', 'hits', 'data', 'results', 'records'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key]) && ! empty($payload[$key])) {
                return $payload[$key][0];
            }
        }
        if (array_is_list($payload) && ! empty($payload) && is_array($payload[0])) {
            return $payload[0];
        }
        if (isset($payload['facilityId']) || isset($payload['facility_id'])) {
            return $payload;
        }

        return null;
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
