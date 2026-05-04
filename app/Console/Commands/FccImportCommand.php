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
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                        'antenna_haat_meters' => $data['haat_meters'] ?? null,
                        'antenna_amsl_meters' => $data['amsl_meters'] ?? null,
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
                        'last_renewal_date' => $data['last_license_date'] ?? null,
                        'grant_date' => $data['last_license_date'] ?? null,
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
     * Fetch one station's public facility record.
     *
     * The FCC's old "publicfiles" and "opendata" Socrata endpoints have
     * shifted — when last verified they returned 404. The reliable
     * surface is the FCC's AM/FM/TV Query CGI at transition.fcc.gov,
     * which has been live for 20+ years. We hit that directly.
     *
     * If you want to wire Socrata or the LMS public API back in, drop
     * the URL into trySocrata() and add a call below.
     */
    protected function fetchFccPublicFacility(string $call): ?array
    {
        // If the user disambiguates with a suffix (e.g. WBZ-AM, KFI-AM),
        // hit that specific query first — otherwise WBZ/KFI/etc. will
        // match unrelated FM stations that happen to share the call.
        $bareCall = strtoupper(preg_replace('/-(FM|TV|AM|LP|LD)$/i', '', $call));
        $suffix = '';
        if (preg_match('/-(AM|FM|TV|LP|LD)$/i', $call, $sm)) {
            $suffix = strtoupper($sm[1]);
        }

        $order = match ($suffix) {
            'AM'         => ['amq', 'fmq', 'tvq'],
            'FM'         => ['fmq', 'amq', 'tvq'],
            'TV', 'LD'   => ['tvq', 'fmq', 'amq'],
            default      => ['fmq', 'amq', 'tvq'],
        };

        foreach ($order as $bin) {
            $hit = $this->tryFccQueryCgi($bin, $bareCall);
            if ($hit) return $hit;
        }

        return null;
    }

    /**
     * Query opendata.fcc.gov Socrata endpoint for a call sign.
     * Currently disabled (404 as of last check) but kept for re-enabling.
     */
    protected function trySocrata(string $datasetId, string $call): ?array
    {
        $url = "https://opendata.fcc.gov/resource/{$datasetId}.json";

        try {
            $response = Http::timeout(8)
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
     * Fall back to the FCC AM/FM/TV Query CGIs at transition.fcc.gov.
     *
     * The CGI returns an HTML page containing JavaScript variable
     * assignments with the actual record data, plus a few <b>-wrapped
     * fields for licensee + licensed date. We parse both.
     *
     * URL example:
     *   https://transition.fcc.gov/fcc-bin/fmq?call=KQED&format=8
     */
    protected function tryFccQueryCgi(string $bin, string $call): ?array
    {
        $html = $this->fetchFccQuery($bin, ['call' => $call, 'format' => 8]);
        if ($html === null) return null;

        // The detail page contains JS variable assignments (either bare
        //   facility_id = '789877';   <- FM
        // or
        //   c_facility_id = '70658';  <- AM
        // ). The list page (returned by AM/TV Query for multi-record
        // calls) carries facid=NNNN links instead. If we got a list page,
        // re-fetch the detail page by facility ID.
        $hasDetail = preg_match("/(?<![A-Za-z0-9_])(?:c_)?facility_id\s*=\s*['\"][1-9]/", $html);
        if (! $hasDetail) {
            if (preg_match('/facid=(\d+)/', $html, $m)) {
                $html = $this->fetchFccQuery($bin, ['list' => 0, 'facid' => $m[1]]);
                if ($html === null) return null;
                if (! preg_match("/(?<![A-Za-z0-9_])(?:c_)?facility_id\s*=\s*['\"][1-9]/", $html)) {
                    return null;
                }
            } else {
                return null;
            }
        }

            // Extract JS variable assignments. FM detail pages use bare
            // names (facility_id = '789877'), AM detail pages prefix
            // with c_ (c_facility_id = '70658', c_callsign = 'WABC').
            // AM also writes some numeric fields unquoted: freq = 770;
            // — accept both forms.
            $jsVal = function (string $name) use ($html): ?string {
                $prefix = "(?<![A-Za-z0-9_])(?:c_)?{$name}\\s*=\\s*";
                if (preg_match("/{$prefix}['\"]([^'\"]*)['\"]/", $html, $m)) {
                    return trim($m[1]);
                }
                if (preg_match("/{$prefix}([0-9]+(?:\\.[0-9]+)?)\\s*;/", $html, $m)) {
                    return trim($m[1]);
                }
                return null;
            };

            $facilityId = $jsVal('facility_id');
            // facility_id = 0 means "no record yet"
            if (! $facilityId || $facilityId === '0') return null;

            $callsign  = $jsVal('c_callsign') ?? $jsVal('c_facility_callsign') ?? $call;
            $service   = strtoupper($jsVal('c_service') ?? '');
            $status    = $jsVal('c_dom_status');
            $city      = $jsVal('c_comm_city_app');
            $state     = $jsVal('c_comm_state_app');
            $freq      = $jsVal('freq');
            $channel   = $jsVal('c_station_channel');
            $erp       = $jsVal('p_erp_max');
            $haat      = $jsVal('p_haat_max');
            $amsl      = $jsVal('p_rcamsl_max');
            $lat       = $jsVal('alat83');
            $lon       = $jsVal('alon83');

            // Licensee + licensed date come from HTML, not JS
            $licensee = null;
            if (preg_match('/Licensee:\s*<b>([^<]+)<\/b>/i', $html, $m)) {
                $licensee = trim($m[1]);
            }
            $licensedDate = null;
            if (preg_match('/Licensed\s+date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $html, $m)) {
                $licensedDate = $m[1];
            }

            $serviceMapped = $this->mapService($service ?: ($bin === 'fmq' ? 'FM' : ($bin === 'amq' ? 'AM' : 'TV')));

            $freqDisplay = match ($serviceMapped) {
                'AM'           => $freq ? "{$freq} kHz"  : '',
                'FM', 'LPFM'   => $freq ? "{$freq} MHz"  : '',
                'TV', 'LPTV'   => $channel ? "Ch. {$channel}" : '',
                default        => $freq ?: $channel ?: '',
            };

            return [
                'facility_id'     => $facilityId,
                'facility_name'   => $callsign,
                'frn'             => '',
                'licensee'        => $licensee ?: 'Unknown',
                'service'         => $serviceMapped,
                'frequency'       => trim($freqDisplay),
                'community'       => $city,
                'state'           => $state,
                'expiration_date' => null,
                'latitude'        => $lat ? (float) $lat : null,
                'longitude'       => $lon ? (float) $lon : null,
                'haat_meters'     => $haat ? (float) $haat : null,
                'amsl_meters'     => $amsl ? (float) $amsl : null,
                'erp_kw'          => $erp ? (float) $erp : null,
                'license_status'  => $status,
                'last_license_date' => $licensedDate,
                '_source_url'     => "transition.fcc.gov/fcc-bin/{$bin}",
            ];
    }

    protected function mapService(string $code): string
    {
        // FCC service designators (LMS):
        //   AM, AB (AM/AM-Booster) → AM
        //   FM, FB (Booster), FX (Translator), FL (LPFM)
        //   DT (Digital TV), TX (TV Translator), LD (LPTV-D), LP (LPTV-A)
        //   CA (Class A TV), DC (Class A digital)
        $code = strtoupper(trim($code));
        return match (true) {
            in_array($code, ['AM', 'AB']) => 'AM',
            in_array($code, ['FM', 'FB', 'FX']) => 'FM',
            $code === 'FL' => 'LPFM',
            $code === 'LPFM' => 'LPFM',
            in_array($code, ['TV', 'DT', 'DC', 'CA']) => 'TV',
            in_array($code, ['LP', 'LD', 'TX']) => 'LPTV',
            default => 'OTHER',
        };
    }

    /**
     * Single-shot fetch from a transition.fcc.gov AM/FM/TV Query CGI.
     * FCC's Akamai edge blocks "Mozilla/5.0 (compatible;...)" UAs; a
     * curl/wget UA passes.
     */
    protected function fetchFccQuery(string $bin, array $params): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'curl/8.5.0',
                    'Accept' => '*/*',
                ])
                ->get("https://transition.fcc.gov/fcc-bin/{$bin}", $params);

            if (! $response->ok()) return null;
            return $response->body() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
