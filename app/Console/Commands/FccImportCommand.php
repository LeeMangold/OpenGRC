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
                    $src = $data['_source_url'] ?? 'unknown';
                    $this->line("  · {$call}: {$data['licensee']} ({$data['service']} {$data['frequency']}) — facility {$data['facility_id']} [src: {$src}]");
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
     * Fetch one station's public facility record from opendata.fcc.gov.
     *
     * The FCC publishes broadcast engineering data via the Socrata SODA
     * API at opendata.fcc.gov — these endpoints are stable and don't
     * require auth for low-volume queries.
     *
     * We try the broadcast engineering dataset first, falling back to
     * the AM/FM Query and LMS endpoints if Socrata returns nothing.
     */
    protected function fetchFccPublicFacility(string $call): ?array
    {
        // Strip any "-FM" / "-TV" suffix for the search; opendata stores the bare call
        $bareCall = strtoupper(preg_replace('/-(FM|TV|AM|LP|LD)$/i', '', $call));

        // 1) opendata.fcc.gov — broadcast engineering (AM/FM/TV) — stable Socrata API
        //    Dataset: "AM, FM, TV Broadcast Engineering Data" (cd28-25ar)
        $hit = $this->trySocrata('cd28-25ar', $bareCall);
        if ($hit) {
            return $this->normalizeSocrata($hit);
        }

        // 2) opendata.fcc.gov — broadcast facility data (alt dataset)
        $hit = $this->trySocrata('iqaq-mbpb', $bareCall);
        if ($hit) {
            return $this->normalizeSocrata($hit);
        }

        // 3) FCC FM Query CGI (pipe-delimited; has been stable since the 90s)
        $hit = $this->tryFccQueryCgi('fmq', $bareCall);
        if ($hit) return $hit;

        $hit = $this->tryFccQueryCgi('amq', $bareCall);
        if ($hit) return $hit;

        $hit = $this->tryFccQueryCgi('tvq', $bareCall);
        if ($hit) return $hit;

        return null;
    }

    /**
     * Query opendata.fcc.gov Socrata endpoint for a call sign.
     */
    protected function trySocrata(string $datasetId, string $call): ?array
    {
        $url = "https://opendata.fcc.gov/resource/{$datasetId}.json";

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'OpenGRC-FCC-Compliance/1.0'])
                ->acceptJson()
                ->get($url, ['call_sign' => $call, '$limit' => 1]);

            if (! $response->ok()) return null;

            $rows = $response->json();
            return is_array($rows) && ! empty($rows) ? $rows[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normalize a Socrata broadcast-engineering row to our schema.
     * Field names vary slightly between FCC datasets; we cover both.
     */
    protected function normalizeSocrata(array $h): array
    {
        $service = $this->mapService((string) (
            $h['service'] ?? $h['service_type'] ?? $h['fac_service'] ?? 'OTHER'
        ));

        $freq = (string) ($h['station_frequency'] ?? $h['frequency'] ?? $h['fac_frequency'] ?? '');
        $channel = (string) ($h['channel'] ?? $h['fac_channel'] ?? '');

        // For AM stations the frequency is in kHz; FM in MHz; TV uses channel.
        $freqDisplay = match (true) {
            str_starts_with($service, 'AM') && $freq !== '' => $freq.' kHz',
            in_array($service, ['FM', 'LPFM']) && $freq !== '' => $freq.' MHz',
            in_array($service, ['TV', 'LPTV']) && $channel !== '' => 'Ch. '.$channel,
            default => $freq ?: $channel,
        };

        return [
            'facility_id'     => (string) ($h['facility_id'] ?? $h['fac_facility_id'] ?? ''),
            'facility_name'   => $h['fac_callsign'] ?? null,
            'frn'             => (string) ($h['licensee_frn'] ?? $h['frn'] ?? ''),
            'licensee'        => $h['licensee_name'] ?? $h['licensee'] ?? $h['fac_organization_name'] ?? 'Unknown',
            'service'         => $service,
            'frequency'       => $freqDisplay,
            'community'       => $h['community_city'] ?? $h['fac_community_city'] ?? null,
            'state'           => $h['community_state'] ?? $h['fac_community_state'] ?? null,
            'expiration_date' => $h['lic_expiration_date'] ?? $h['license_expiration_date'] ?? null,
            '_source_url'     => 'opendata.fcc.gov',
        ];
    }

    /**
     * Fall back to the FCC AM/FM/TV Query CGIs with format=4 (pipe-delimited).
     * These have been the most stable FCC endpoint over the past 20 years.
     */
    protected function tryFccQueryCgi(string $bin, string $call): ?array
    {
        $url = "https://transition.fcc.gov/fcc-bin/{$bin}";

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'OpenGRC-FCC-Compliance/1.0'])
                ->get($url, ['call' => $call, 'format' => 4]);

            if (! $response->ok()) return null;
            $body = trim($response->body());
            if ($body === '' || ! str_contains($body, '|')) return null;

            // Take the first non-comment data line
            foreach (preg_split("/\r?\n/", $body) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '*')) continue;
                $cols = explode('|', $line);
                if (count($cols) < 5) continue;

                // FM Query format 4 columns (approximate, public docs):
                //   call|service|status|frequency|channel|...|licensee|city|state|...|facility_id|...
                $service = $bin === 'fmq' ? 'FM' : ($bin === 'amq' ? 'AM' : 'TV');
                $freq = trim($cols[3] ?? '');
                $channel = trim($cols[4] ?? '');
                $licensee = trim($cols[6] ?? $cols[7] ?? '');
                $city = trim($cols[8] ?? '');
                $state = trim($cols[9] ?? '');

                // Hunt for a numeric facility ID elsewhere in the row
                $facilityId = '';
                foreach ($cols as $c) {
                    $c = trim($c);
                    if (ctype_digit($c) && strlen($c) >= 4 && strlen($c) <= 7) {
                        $facilityId = $c;
                        break;
                    }
                }

                $freqDisplay = $service === 'AM' ? "{$freq} kHz" : ($service === 'FM' ? "{$freq} MHz" : "Ch. {$channel}");

                return [
                    'facility_id'     => $facilityId,
                    'facility_name'   => null,
                    'frn'             => '',
                    'licensee'        => $licensee ?: 'Unknown',
                    'service'         => $service,
                    'frequency'       => trim($freqDisplay),
                    'community'       => $city,
                    'state'           => $state,
                    'expiration_date' => null,
                    '_source_url'     => "transition.fcc.gov/fcc-bin/{$bin}",
                ];
            }
        } catch (\Throwable $e) {
            return null;
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
