<?php

namespace App\Console\Commands;

use App\Models\FccLicense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * LMS augmentation pass — fills FRN (FCC Registration Number) and
 * other LMS-only fields onto licenses already imported from CDBS.
 *
 * Source:
 *   https://enterpriseefiling.fcc.gov/dataentry/public/tv/publicFacilityDetails.html?facilityId={id}
 *
 * The FCC's modern LMS public facility-details page exposes FRN +
 * facility status + status date + title (full station name) per
 * facility. CDBS bulk dumps don't include FRN, so we have to scrape
 * LMS one facility at a time. We rate-limit to be polite and keep
 * the run resumable.
 *
 * Usage:
 *   php artisan fcc:import-lms                  # all licenses missing FRN
 *   php artisan fcc:import-lms --limit=200      # batch
 *   php artisan fcc:import-lms --calls=KQED,WTOP # specific stations
 *   php artisan fcc:import-lms --sleep=300      # 300ms between requests
 */
class FccImportLmsCommand extends Command
{
    protected $signature = 'fcc:import-lms
                            {--limit= : Cap the number of stations augmented this run}
                            {--calls= : Comma-separated call signs (skips the missing-FRN filter)}
                            {--sleep=200 : Delay (ms) between FCC requests}
                            {--force : Re-fetch even if FRN already populated}';

    protected $description = 'Augment imported licenses with FRN + LMS facility details';

    private const LMS_DETAIL = 'https://enterpriseefiling.fcc.gov/dataentry/public/tv/publicFacilityDetails.html';

    public function handle(): int
    {
        $sleepMs = (int) $this->option('sleep');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = FccLicense::query()->with('facility');

        if ($calls = $this->option('calls')) {
            $list = collect(explode(',', $calls))->map(fn ($c) => strtoupper(trim($c)))->filter()->all();
            $query->whereIn('call_sign', $list);
        } elseif (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('frn')->orWhere('frn', '')->orWhere('frn', 'like', 'Pending%');
            });
        }

        if ($limit) $query->limit($limit);

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to augment — all targeted licenses already have an FRN.');
            return self::SUCCESS;
        }

        $this->info("LMS augmentation — {$total} licenses to fetch (rate-limited)…");
        $progress = $this->output->createProgressBar($total);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
        $progress->setMessage('');
        $progress->start();

        $hits = 0;
        $misses = 0;

        $query->chunk(200, function ($chunk) use (&$hits, &$misses, $progress, $sleepMs) {
            foreach ($chunk as $license) {
                $facilityId = optional($license->facility)->facility_id;
                if (! $facilityId) {
                    $progress->advance();
                    continue;
                }

                $data = $this->fetchLmsFacility($facilityId);
                $progress->setMessage($license->call_sign);
                $progress->advance();

                if (! $data) { $misses++; continue; }

                $updates = [];
                if (! empty($data['frn'])) {
                    $updates['frn'] = $data['frn'];
                }
                if ($updates) {
                    DB::table('fcc_licenses')->where('id', $license->id)->update($updates);
                    $hits++;
                }
                if (! empty($data['title']) || ! empty($data['status_date'])) {
                    $facUpdates = [];
                    if (! empty($data['title']) && empty($license->facility->name)) {
                        $facUpdates['name'] = $data['title'];
                    }
                    if ($facUpdates) {
                        DB::table('fcc_facilities')->where('id', $license->facility->id)->update($facUpdates);
                    }
                }

                if ($sleepMs > 0) usleep($sleepMs * 1000);
            }
        });

        $progress->finish();
        $this->newLine(2);
        $this->info("Updated FRN on {$hits} licenses ({$misses} misses).");

        return self::SUCCESS;
    }

    /**
     * Scrape one LMS facility-details page. Returns a normalized array
     * of LMS-only fields (FRN, title, status date, facility status).
     */
    protected function fetchLmsFacility(string $facilityId): ?array
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'curl/8.5.0',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get(self::LMS_DETAIL, ['facilityId' => $facilityId]);

            if (! $response->ok()) return null;
            $html = $response->body();
            if ($html === '' || ! str_contains($html, 'FRN')) return null;

            return [
                'frn'           => $this->dtdd($html, 'FRN'),
                'title'         => $this->dtdd($html, 'Title'),
                'name'          => $this->dtdd($html, 'Name'),
                'service'       => $this->dtdd($html, 'Service'),
                'status'        => $this->dtdd($html, 'Facility Status'),
                'status_date'   => $this->dtdd($html, 'Status Date'),
                'facility_type' => $this->dtdd($html, 'Facility Type'),
                'station_type'  => $this->dtdd($html, 'Station Type'),
                'community'     => $this->dtdd($html, 'Community'),
                'frequency'     => $this->dtdd($html, 'Frequency'),
                'digital_op'    => $this->dtdd($html, 'Digital Operation'),
                'email'         => $this->dtdd($html, 'Email'),
                'phone'         => $this->dtdd($html, 'Phone'),
                'address'       => $this->dtdd($html, 'Address'),
                'country'       => $this->dtdd($html, 'Country'),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Pull the value from a <dt>Label:</dt> <dd>value</dd> pair.
     */
    private function dtdd(string $html, string $label): ?string
    {
        $pattern = '/<dt>'.preg_quote($label, '/').':<\/dt>\s*<dd[^>]*>([^<]+)<\/dd>/i';
        if (preg_match($pattern, $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
        }
        return null;
    }
}
