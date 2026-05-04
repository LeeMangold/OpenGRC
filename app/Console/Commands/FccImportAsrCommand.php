<?php

namespace App\Console\Commands;

use App\Models\FccAsrRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/**
 * Bulk-import every Antenna Structure Registration (ASR) from the FCC's
 * Universal Licensing System (ULS) public data files.
 *
 * Source:
 *   https://data.fcc.gov/download/pub/uls/complete/r_tower.zip   (~1 MB)
 *   https://data.fcc.gov/download/pub/uls/complete/a_tower.zip   (~1 MB)
 *
 * Each .zip unpacks to several pipe-delimited .dat files. The key tables
 * for ASRs:
 *   RA.dat — Registration record (asrn, file_number, registration_action)
 *   EN.dat — Entity (registrant_name, address)
 *   CO.dat — Coordinates (lat/lon, height)
 *
 * ULS file column docs: https://www.fcc.gov/sites/default/files/pa_ddef.pdf
 */
class FccImportAsrCommand extends Command
{
    protected $signature = 'fcc:import-asr
                            {--limit= : Cap the number of ASRs imported (smoke test)}
                            {--skip-download : Reuse cached zip in storage/app/uls/}';

    protected $description = 'Bulk-import every Antenna Structure Registration (ASR) from FCC ULS';

    private const ULS_BASE = 'https://data.fcc.gov/download/pub/uls/complete/';

    public function handle(): int
    {
        $cacheDir = storage_path('app/uls');
        if (! is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        $this->info('FCC ULS bulk import — Antenna Structure Registrations');
        $this->newLine();

        $files = ['r_tower.zip', 'a_tower.zip'];
        foreach ($files as $f) {
            $local = "{$cacheDir}/{$f}";
            if (! $this->option('skip-download') || ! file_exists($local)) {
                $this->info("Downloading {$f}…");
                if (! $this->download(self::ULS_BASE.$f, $local)) {
                    $this->warn("  failed: {$f} (continuing)");
                    continue;
                }
                $this->line('  '.number_format(filesize($local) / 1024, 1).' KB');
            } else {
                $this->line("Cached: {$f}");
            }
            // Unzip
            $zip = new ZipArchive;
            if ($zip->open($local) === true) {
                $zip->extractTo($cacheDir);
                $zip->close();
            }
        }
        $this->newLine();

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // Index entities (registrant names) for owner lookup
        $this->info('Indexing entities (EN.dat)…');
        $entities = $this->indexEntities("{$cacheDir}/EN.dat");
        $this->line('  '.count($entities).' entities indexed');

        // Index coordinates (CO.dat) for ASR location lookup
        $this->info('Indexing coordinates (CO.dat)…');
        $coords = $this->indexCoordinates("{$cacheDir}/CO.dat");
        $this->line('  '.count($coords).' coordinates indexed');

        // Stream RA.dat → upsert ASR records
        $this->info('Importing ASR registrations (RA.dat)…');
        $count = $this->importRa("{$cacheDir}/RA.dat", $entities, $coords, $limit);
        $this->line("  {$count} ASRs imported");
        $this->newLine();

        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * Build unique_system_id → entity_name mapping from EN.dat.
     * EN.dat columns (typical ULS): record_type|unique_system_id|uls_file_num|
     *   ebf_number|call_sign|entity_type|licensee_id|entity_name|first_name|...
     */
    private function indexEntities(string $path): array
    {
        $map = [];
        if (! file_exists($path)) return $map;

        $fh = fopen($path, 'r');
        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 8) continue;
            $sysId = trim($cols[1]);
            $name  = trim($cols[7]);
            if ($sysId !== '' && $name !== '') {
                $map[$sysId] = $name;
            }
        }
        fclose($fh);
        return $map;
    }

    /**
     * Build unique_system_id → ['lat'=>..., 'lon'=>..., 'height_m'=>...] from CO.dat.
     * CO.dat columns: record_type|unique_system_id|uls_file_num|callsign|
     *   loc_action|loc_type|loc_class|loc_seqid|loc_name|loc_addr|loc_city|
     *   loc_county|loc_state|radius_op|loc_zip|...|lat_deg|lat_min|lat_sec|
     *   lat_dir|lon_deg|lon_min|lon_sec|lon_dir|max_height_m|...
     */
    private function indexCoordinates(string $path): array
    {
        $map = [];
        if (! file_exists($path)) return $map;

        $fh = fopen($path, 'r');
        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 30) continue;

            $sysId = trim($cols[1]);
            // Approximate column positions; ULS format is documented at
            // https://www.fcc.gov/sites/default/files/pa_ddef.pdf
            $latDeg = (float) ($cols[19] ?? 0);
            $latMin = (float) ($cols[20] ?? 0);
            $latSec = (float) ($cols[21] ?? 0);
            $latDir = trim($cols[22] ?? '');
            $lonDeg = (float) ($cols[23] ?? 0);
            $lonMin = (float) ($cols[24] ?? 0);
            $lonSec = (float) ($cols[25] ?? 0);
            $lonDir = trim($cols[26] ?? '');
            $heightM = (float) ($cols[28] ?? 0);

            $lat = $this->dms($latDir, $latDeg, $latMin, $latSec);
            $lon = $this->dms($lonDir, $lonDeg, $lonMin, $lonSec);

            if ($lat !== null || $lon !== null || $heightM > 0) {
                $map[$sysId] = ['lat' => $lat, 'lon' => $lon, 'height_m' => $heightM ?: null];
            }
        }
        fclose($fh);
        return $map;
    }

    /**
     * Stream RA.dat → upsert into fcc_asr_registrations.
     * RA.dat columns: record_type|content_indicator|file_number|
     *   registration_number|unique_system_identifier|application_purpose|
     *   previous_purpose|input_source_code|status_code|date_entered|
     *   date_received|date_issued|date_constructed|date_dismantled|
     *   date_action|...|height_of_structure|ground_elevation|overall_height|
     *   structure_type|...
     */
    private function importRa(string $path, array $entities, array $coords, ?int $limit): int
    {
        if (! file_exists($path)) {
            $this->warn('  RA.dat not found');
            return 0;
        }

        $now = now();
        $count = 0;
        $batch = [];

        $fh = fopen($path, 'r');
        $progress = $this->output->createProgressBar();
        $progress->setFormat(' %current% ASRs [%bar%] %elapsed:6s%');
        $progress->start();

        while (! feof($fh)) {
            $line = fgets($fh);
            if ($line === false) break;
            $cols = explode('|', rtrim($line, "\r\n"));
            if (count($cols) < 20) continue;

            $sysId    = trim($cols[4] ?? '');
            $asrNum   = trim($cols[3] ?? '');
            $fileNum  = trim($cols[2] ?? '');
            $statusCd = trim($cols[8] ?? '');

            if ($asrNum === '' || ! ctype_digit($asrNum)) continue;
            if ($limit !== null && $count >= $limit) break;

            $owner = $entities[$sysId] ?? null;
            $coord = $coords[$sysId] ?? ['lat' => null, 'lon' => null, 'height_m' => null];

            $structureType = $this->mapStructureType($cols[34] ?? '');

            $batch[$asrNum] = [
                'asr_number'              => $asrNum,
                'owner'                   => $owner ?: 'Unknown',
                'structure_type'          => $structureType,
                'overall_height_meters'   => $coord['height_m'],
                'latitude'                => $coord['lat'],
                'longitude'               => $coord['lon'],
                'faa_study_number'        => trim($cols[2] ?? ''),
                'created_at'              => $now,
                'updated_at'              => $now,
            ];
            $count++;
            $progress->advance();

            if (count($batch) >= 500) {
                $this->flushAsrs($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) $this->flushAsrs($batch);

        fclose($fh);
        $progress->finish();
        $this->newLine();

        return $count;
    }

    private function flushAsrs(array $batch): void
    {
        DB::table('fcc_asr_registrations')->upsert(
            array_values($batch),
            ['asr_number'],
            ['owner', 'structure_type', 'overall_height_meters',
             'latitude', 'longitude', 'faa_study_number', 'updated_at']
        );
    }

    private function mapStructureType(string $code): ?string
    {
        $code = strtoupper(trim($code));
        return match ($code) {
            'GTOWER', 'GUYED', 'GTW'  => 'guyed',
            'STOWER', 'SELF', 'STW'   => 'self_supporting',
            'POLE', 'MAST'            => 'monopole',
            'BANT', 'BLDG', 'BUILDING', 'BMAST' => 'building',
            default                   => null,
        };
    }

    private function dms(string $dir, $deg, $min, $sec): ?float
    {
        $deg = (float) $deg; $min = (float) $min; $sec = (float) $sec;
        if ($deg == 0 && $min == 0 && $sec == 0) return null;
        $dec = $deg + ($min / 60) + ($sec / 3600);
        return in_array(strtoupper($dir), ['S', 'W'], true) ? -$dec : $dec;
    }

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
}
