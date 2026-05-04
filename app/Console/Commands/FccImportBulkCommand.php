<?php

namespace App\Console\Commands;

use App\Models\FccFacility;
use App\Models\FccLicense;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/**
 * Bulk-import every U.S. broadcast facility from the FCC's CDBS daily
 * data dumps. The CDBS dataset is the historical broadcast database
 * mirrored daily at:
 *   https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/
 *
 * Files used:
 *   - facility.zip      → facility.dat (master facility table)
 *   - am_eng_data.zip   → am_eng_data.dat (AM engineering: power, lat/lon)
 *   - fm_eng_data.zip   → fm_eng_data.dat (FM engineering: ERP, HAAT, lat/lon)
 *   - tv_eng_data.zip   → tv_eng_data.dat (TV engineering: channel, lat/lon)
 *
 * All files are pipe-delimited, no header. Column order follows the
 * FCC's published CDBS schema (cdbsdoc.txt).
 *
 * Usage:
 *   php artisan fcc:import-bulk             # full sync (downloads ~22MB, imports ~30K stations)
 *   php artisan fcc:import-bulk --limit=500 # smoke test
 *   php artisan fcc:import-bulk --skip-download   # use cached files in storage/app/cdbs/
 *   php artisan fcc:import-bulk --service=fm      # only AM/FM/TV
 */
class FccImportBulkCommand extends Command
{
    protected $signature = 'fcc:import-bulk
                            {--limit= : Cap the number of facilities imported per service (smoke test)}
                            {--skip-download : Reuse cached zip files in storage/app/cdbs/}
                            {--skip-licensees : Don\'t pull party/fac_party (saves ~16MB / faster)}
                            {--service= : Limit to am, fm, or tv}';

    protected $description = 'Bulk-import every U.S. broadcast facility from FCC CDBS daily dumps';

    private const CDBS_BASE = 'https://transition.fcc.gov/Bureaus/MB/Databases/cdbs/';

    /**
     * Column order in facility.dat (per FCC cdbsdoc.txt). 0-indexed.
     * Verified against CDBS documentation.
     */
    private const FACILITY_COLS = [
        'comm_city'            => 0,
        'comm_state'           => 1,
        'eeo_rpt_ind'          => 2,
        'fac_address1'         => 3,
        'fac_address2'         => 4,
        'fac_callsign'         => 5,
        'fac_channel'          => 6,
        'fac_city'             => 7,
        'fac_country'          => 8,
        'fac_frequency'        => 9,
        'fac_service'          => 10,
        'fac_state'            => 11,
        'fac_status_date'      => 12,
        'fac_type'             => 13,
        'facility_id'          => 14,
        'lic_expiration_date'  => 15,
        'fac_status'           => 16,
        'fac_zip1'             => 17,
        'fac_zip2'             => 18,
        'station_type'         => 19,
        'assoc_facility_id'    => 20,
        'callsign_eff_date'    => 21,
        'tsid_ntsc'            => 22,
        'tsid_dtv'             => 23,
        'digital_status'       => 24,
        'sat_tv'               => 25,
        'network_affil'        => 26,
        'nielsen_dma'          => 27,
        'tv_virtual_channel'   => 28,
        'last_change_date'     => 29,
    ];

    /**
     * Column positions verified empirically against current CDBS dumps.
     * Each {svc}_eng_data.dat has slightly different column ordering
     * (FM: 73 cols, TV: 75 cols, AM: 17 cols — AM has no lat/lon).
     */
    private const FM_ENG_COLS = [
        'service'      => 7,
        'facility_id'  => 20,
        'erp_kw'       => 29,
        'lat_deg'      => 30,
        'lat_dir'      => 31,
        'lat_min'      => 32,
        'lat_sec'      => 33,
        'lon_deg'      => 34,
        'lon_dir'      => 35,
        'lon_min'      => 36,
        'lon_sec'      => 37,
        'haat_meters'  => 40,
        'amsl_meters'  => 48,
        'station_class'=> 50,
    ];

    private const TV_ENG_COLS = [
        'facility_id'  => 21,
        'lat_deg'      => 29,
        'lat_dir'      => 30,
        'lat_min'      => 31,
        'lat_sec'      => 32,
        'lon_deg'      => 33,
        'lon_dir'      => 34,
        'lon_min'      => 35,
        'lon_sec'      => 36,
        'haat_meters'  => 41,
        'erp_kw'       => 42,
        'amsl_meters'  => 47,
    ];

    public function handle(): int
    {
        $cacheDir = storage_path('app/cdbs');
        if (! is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        $services = $this->option('service')
            ? [strtolower($this->option('service'))]
            : ['am', 'fm', 'tv'];

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('FCC CDBS bulk import — every U.S. broadcast facility');
        $this->info("Cache dir: {$cacheDir}");
        $this->newLine();

        // 1. Download (or use cached) zip files
        $files = ['facility.zip'];
        foreach ($services as $svc) $files[] = "{$svc}_eng_data.zip";

        foreach ($files as $f) {
            $local = "{$cacheDir}/{$f}";
            if (! $this->option('skip-download') || ! file_exists($local)) {
                $this->info("Downloading {$f}…");
                if (! $this->download(self::CDBS_BASE.$f, $local)) {
                    $this->error("  failed: ".self::CDBS_BASE.$f);
                    return self::FAILURE;
                }
                $this->line('  '.number_format(filesize($local) / 1024 / 1024, 1).' MB');
            } else {
                $this->line("Cached: {$f}");
            }
        }
        $this->newLine();

        // 2. Unzip
        foreach ($files as $f) {
            $zip = new ZipArchive;
            if ($zip->open("{$cacheDir}/{$f}") !== true) {
                $this->error("Failed to open {$f}");
                return self::FAILURE;
            }
            $zip->extractTo($cacheDir);
            $zip->close();
        }

        // 3. Import facilities (master table)
        $this->info('Importing facilities (broadcast master table)…');
        $facilityIdMap = $this->importFacilities("{$cacheDir}/facility.dat", $services, $limit);
        $this->line('  '.number_format(count($facilityIdMap)).' facilities imported');
        $this->newLine();

        // 4. Augment with engineering data (lat/lon, ERP, HAAT) per service.
        //    AM has no coordinates in am_eng_data — skip it (AM lat/lon
        //    lives in am_ant_sys.dat which requires a separate join path).
        foreach ($services as $svc) {
            if ($svc === 'am') continue;
            $eng = "{$cacheDir}/{$svc}_eng_data.dat";
            if (! file_exists($eng)) continue;

            $cols = $svc === 'tv' ? self::TV_ENG_COLS : self::FM_ENG_COLS;
            $this->info("Applying {$svc}_eng_data → coordinates / ERP / HAAT…");
            $count = $this->applyEngineeringData($eng, $facilityIdMap, $cols);
            $this->line("  {$count} facilities updated with engineering data");
        }
        $this->newLine();

        // 5. Optional: pull licensee names from party.zip + fac_party.zip
        if (! $this->option('skip-licensees')) {
            $this->info('Pulling licensee names (party.zip + fac_party.zip)…');
            $partyOk = $this->option('skip-download') && file_exists("{$cacheDir}/party.zip")
                ? true
                : $this->download(self::CDBS_BASE.'party.zip', "{$cacheDir}/party.zip");
            $facPartyOk = $this->option('skip-download') && file_exists("{$cacheDir}/fac_party.zip")
                ? true
                : $this->download(self::CDBS_BASE.'fac_party.zip', "{$cacheDir}/fac_party.zip");

            if ($partyOk && $facPartyOk) {
                $this->unzipIfNeeded("{$cacheDir}/party.zip", $cacheDir);
                $this->unzipIfNeeded("{$cacheDir}/fac_party.zip", $cacheDir);
                $count = $this->applyLicensees("{$cacheDir}/party.dat", "{$cacheDir}/fac_party.dat");
                $this->line("  {$count} licenses updated with real licensee names");
            } else {
                $this->warn('  party data unavailable; skipping licensee names');
            }
        }
        $this->newLine();

        $this->info('Done. Run `php artisan fcc:sync` for the full pipeline.');
        return self::SUCCESS;
    }

    private function unzipIfNeeded(string $zipPath, string $destDir): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($destDir);
            $zip->close();
        }
    }

    /**
     * Apply licensee names from party.dat (party_id → name) joined to
     * fac_party.dat (party_id ↔ facility_id, role_code='LIC' = licensee).
     */
    private function applyLicensees(string $partyPath, string $facPartyPath): int
    {
        if (! file_exists($partyPath) || ! file_exists($facPartyPath)) return 0;

        // Load party_id → name into memory (~50K rows, name string only)
        $parties = [];
        $fh = fopen($partyPath, 'r');
        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 8) continue;
            // party.dat columns: party_id, name, attention_line, addr1, addr2, ...
            $partyId = trim($cols[0]);
            $name = trim($cols[1]);
            if ($partyId !== '' && $name !== '') {
                $parties[$partyId] = $name;
            }
        }
        fclose($fh);

        // Stream fac_party.dat: facility_id, party_id, role_code (=LIC for licensee)
        $fh = fopen($facPartyPath, 'r');
        $batch = [];
        $touched = 0;
        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 5) continue;

            $facilityId = trim($cols[0] ?? '');
            $partyId    = trim($cols[1] ?? '');
            $roleCode   = strtoupper(trim($cols[2] ?? ''));

            if ($roleCode !== 'LIC' && $roleCode !== 'LICENSEE' && $roleCode !== 'OWNER') continue;
            if (! isset($parties[$partyId])) continue;

            $batch[$facilityId] = $parties[$partyId];

            if (count($batch) >= 1000) {
                $touched += $this->flushLicensees($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) $touched += $this->flushLicensees($batch);
        fclose($fh);

        return $touched;
    }

    private function flushLicensees(array $batch): int
    {
        if (empty($batch)) return 0;

        return DB::transaction(function () use ($batch) {
            $touched = 0;
            $pdo = DB::connection()->getPdo();
            $facUpdate = $pdo->prepare(
                'UPDATE fcc_facilities SET owner = ? WHERE facility_id = ?'
            );
            $licUpdate = $pdo->prepare(
                'UPDATE fcc_licenses SET licensee = ? WHERE facility_id IN '
                .'(SELECT id FROM fcc_facilities WHERE facility_id = ?)'
            );
            foreach ($batch as $facilityId => $licensee) {
                $facUpdate->execute([$licensee, $facilityId]);
                $licUpdate->execute([$licensee, $facilityId]);
                $touched += $licUpdate->rowCount();
            }
            return $touched;
        });
    }

    /**
     * Stream-download a URL to disk with a curl-style UA (FCC blocks Mozilla).
     */
    private function download(string $url, string $dest): bool
    {
        $ch = curl_init($url);
        $fp = fopen($dest, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'curl/8.5.0',
            CURLOPT_HTTPHEADER => ['Accept: */*'],
            CURLOPT_TIMEOUT => 600,
            CURLOPT_FAILONERROR => true,
        ]);
        $ok = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return (bool) $ok && filesize($dest) > 0;
    }

    /**
     * Stream facility.dat, upserting into fcc_facilities + fcc_licenses.
     * Returns [facility_id => internal_facility_pk] for the engineering pass.
     */
    private function importFacilities(string $path, array $services, ?int $limit): array
    {
        $serviceFilter = $this->buildServiceFilter($services);
        $facilityCol = self::FACILITY_COLS;

        $facMap = [];
        $countByService = [];
        $facBatch = [];
        $licBatch = [];
        $now = now();

        $fh = fopen($path, 'r');
        if (! $fh) return [];

        $progress = $this->output->createProgressBar();
        $progress->setFormat(' %current% facilities [%bar%] %elapsed:6s%');
        $progress->start();

        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 30) continue;

            $service = trim($cols[$facilityCol['fac_service']]);
            if (! in_array(strtoupper($service), $serviceFilter, true)) continue;

            $facilityId = trim($cols[$facilityCol['facility_id']]);
            if ($facilityId === '' || ! ctype_digit($facilityId)) continue;

            $callsign = trim($cols[$facilityCol['fac_callsign']]);
            if ($callsign === '') continue;

            $svcUpper = strtoupper($service);
            $countByService[$svcUpper] = ($countByService[$svcUpper] ?? 0) + 1;
            if ($limit !== null && $countByService[$svcUpper] > $limit) continue;

            $city  = trim($cols[$facilityCol['comm_city']]);
            $state = trim($cols[$facilityCol['comm_state']]);
            $country = trim($cols[$facilityCol['fac_country']]);
            $channel = trim($cols[$facilityCol['fac_channel']]);
            $frequency = trim($cols[$facilityCol['fac_frequency']]);
            $licExp  = $this->parseDate($cols[$facilityCol['lic_expiration_date']] ?? null);
            $callsignEff = $this->parseDate($cols[$facilityCol['callsign_eff_date']] ?? null);
            $facStatus = trim($cols[$facilityCol['fac_status']]);
            $networkAffil = trim($cols[$facilityCol['network_affil']]);
            $dma = trim($cols[$facilityCol['nielsen_dma']]);

            // Skip non-US for the demo
            if ($country !== '' && strtoupper($country) !== 'US') continue;

            $facBatch[$facilityId] = [
                'facility_id' => $facilityId,
                'name' => $callsign.($city ? " — {$city}" : ''),
                'community_of_license' => $city ?: null,
                'state' => $state ?: null,
                'owner' => null,  // populated later from party.zip if needed
                'notes' => $networkAffil ? "Network: {$networkAffil}".($dma ? " | DMA: {$dma}" : '') : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $licBatch[] = [
                'call_sign' => $callsign,
                'frn' => '',
                'licensee' => 'Pending — see CDBS party tables',
                'service' => $this->mapService($svcUpper),
                'channel_or_frequency' => $this->formatFreq($svcUpper, $frequency, $channel),
                'expiration_date' => $licExp,
                'last_renewal_date' => $licExp ? $licExp->copy()->subYears(8) : null,
                'grant_date' => $callsignEff,
                'status' => $this->mapStatus($facStatus),
                'compliance_score' => 100.00,
                'cdbs_facility_id' => $facilityId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $progress->advance();

            if (count($facBatch) >= 500) {
                $this->flushBatch($facBatch, $licBatch, $facMap);
                $facBatch = [];
                $licBatch = [];
            }
        }

        if (! empty($facBatch)) {
            $this->flushBatch($facBatch, $licBatch, $facMap);
        }

        fclose($fh);
        $progress->finish();
        $this->newLine();

        return $facMap;
    }

    /**
     * Bulk-upsert a batch of facilities + licenses, mapping
     * cdbs facility_id → internal pk for downstream engineering pass.
     */
    private function flushBatch(array $facs, array $licenses, array &$facMap): void
    {
        if (empty($facs)) return;

        DB::table('fcc_facilities')->upsert(
            array_values($facs),
            ['facility_id'],
            ['name', 'community_of_license', 'state', 'notes', 'updated_at']
        );

        $internalIds = DB::table('fcc_facilities')
            ->whereIn('facility_id', array_keys($facs))
            ->pluck('id', 'facility_id');

        $licRows = [];
        foreach ($licenses as $lic) {
            $cdbsId = $lic['cdbs_facility_id'];
            unset($lic['cdbs_facility_id']);
            $lic['facility_id'] = $internalIds[$cdbsId] ?? null;
            $facMap[$cdbsId] = $lic['facility_id'];
            $licRows[] = $lic;
        }

        if (! empty($licRows)) {
            DB::table('fcc_licenses')->upsert(
                $licRows,
                ['call_sign'],
                ['licensee', 'service', 'channel_or_frequency', 'expiration_date',
                 'last_renewal_date', 'grant_date', 'status', 'facility_id', 'updated_at']
            );
        }
    }

    /**
     * Stream {fm,tv}_eng_data.dat and update fcc_facilities with
     * lat/lon/HAAT/AMSL pulled from the engineering record. Column
     * positions vary per service — pass the appropriate map.
     */
    private function applyEngineeringData(string $path, array $facMap, array $cols): int
    {
        $fh = fopen($path, 'r');
        if (! $fh) return 0;

        $minCols = max($cols) + 1;
        $count = 0;
        $batch = [];

        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $row = explode('|', rtrim($line, "\r\n"));
            if (count($row) < $minCols) continue;

            $facilityId = trim($row[$cols['facility_id']] ?? '');
            if (! isset($facMap[$facilityId])) continue;

            $lat = $this->dms2decimal(
                $row[$cols['lat_dir']] ?? '',
                $row[$cols['lat_deg']] ?? 0,
                $row[$cols['lat_min']] ?? 0,
                $row[$cols['lat_sec']] ?? 0
            );
            $lon = $this->dms2decimal(
                $row[$cols['lon_dir']] ?? '',
                $row[$cols['lon_deg']] ?? 0,
                $row[$cols['lon_min']] ?? 0,
                $row[$cols['lon_sec']] ?? 0
            );
            $haat = (float) ($row[$cols['haat_meters']] ?? 0);
            $amsl = (float) ($row[$cols['amsl_meters']] ?? 0);

            if ($lat === null && $lon === null && $haat == 0 && $amsl == 0) continue;

            $batch[] = [
                'facility_id' => $facilityId,
                'latitude' => $lat,
                'longitude' => $lon,
                'antenna_haat_meters' => $haat ?: null,
                'antenna_amsl_meters' => $amsl ?: null,
            ];

            if (count($batch) >= 500) {
                $count += $this->flushEngineering($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) $count += $this->flushEngineering($batch);

        fclose($fh);
        return $count;
    }

    /**
     * Flush an engineering batch as a single transaction — without
     * wrapping the per-row UPDATEs in a transaction SQLite fsyncs
     * after every statement, making the pass take 30+ minutes for
     * the FM dataset. With a wrap it finishes in seconds.
     */
    private function flushEngineering(array $batch): int
    {
        if (empty($batch)) return 0;

        return DB::transaction(function () use ($batch) {
            $touched = 0;
            $stmt = DB::connection()->getPdo()->prepare(
                'UPDATE fcc_facilities SET latitude = ?, longitude = ?, '
                .'antenna_haat_meters = ?, antenna_amsl_meters = ? '
                .'WHERE facility_id = ?'
            );
            foreach ($batch as $row) {
                $stmt->execute([
                    $row['latitude'],
                    $row['longitude'],
                    $row['antenna_haat_meters'],
                    $row['antenna_amsl_meters'],
                    $row['facility_id'],
                ]);
                $touched += $stmt->rowCount();
            }
            return $touched;
        });
    }

    /* ---------- helpers ---------- */

    private function buildServiceFilter(array $services): array
    {
        $map = [
            'am' => ['AM', 'AB'],
            'fm' => ['FM', 'FB', 'FX', 'FL'],
            'tv' => ['TV', 'DT', 'DC', 'CA', 'LD', 'LP', 'TX'],
        ];
        $out = [];
        foreach ($services as $s) {
            $out = array_merge($out, $map[$s] ?? []);
        }
        return $out ?: ['AM', 'FM', 'TV', 'FB', 'FX', 'FL', 'DT', 'DC', 'CA', 'LD', 'LP', 'TX', 'AB'];
    }

    private function mapService(string $code): string
    {
        return match (true) {
            in_array($code, ['AM', 'AB']) => 'AM',
            in_array($code, ['FM', 'FB', 'FX']) => 'FM',
            $code === 'FL' => 'LPFM',
            in_array($code, ['TV', 'DT', 'DC', 'CA']) => 'TV',
            in_array($code, ['LP', 'LD', 'TX']) => 'LPTV',
            default => 'OTHER',
        };
    }

    private function mapStatus(string $cdbs): string
    {
        $cdbs = strtoupper(trim($cdbs));
        return match ($cdbs) {
            'LICEN', 'LIC', 'LICENSED' => 'active',
            'EXPIR', 'EXPIRED'         => 'non_compliant',
            'CANCEL', 'CANCELLED'      => 'cancelled',
            'SILENT', 'SILEN'          => 'silent',
            default                    => 'active',
        };
    }

    private function formatFreq(string $service, string $freq, string $channel): ?string
    {
        $freq = trim($freq);
        $channel = trim($channel);
        return match (true) {
            in_array($service, ['AM', 'AB']) && $freq !== '' => "{$freq} kHz",
            in_array($service, ['FM', 'FB', 'FX', 'FL']) && $freq !== '' => "{$freq} MHz",
            in_array($service, ['TV', 'DT', 'DC', 'CA', 'LD', 'LP', 'TX']) && $channel !== '' => "Ch. {$channel}",
            default => $freq !== '' ? $freq : ($channel !== '' ? "Ch. {$channel}" : null),
        };
    }

    private function parseDate(?string $raw): ?Carbon
    {
        if (! $raw || trim($raw) === '') return null;
        try {
            // CDBS dates are typically ISO yyyy-mm-dd
            return Carbon::parse(trim($raw));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function dms2decimal(string $dir, $deg, $min, $sec): ?float
    {
        $deg = (float) $deg; $min = (float) $min; $sec = (float) $sec;
        if ($deg == 0 && $min == 0 && $sec == 0) return null;
        $dec = $deg + ($min / 60) + ($sec / 3600);
        return in_array(strtoupper(trim($dir)), ['S', 'W'], true) ? -$dec : $dec;
    }
}
