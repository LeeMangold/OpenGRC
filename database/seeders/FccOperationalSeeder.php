<?php

namespace Database\Seeders;

use App\Models\FccAsrRegistration;
use App\Models\FccEasTest;
use App\Models\FccFormFiling;
use App\Models\FccIssuesProgramsEntry;
use App\Models\FccIssuesProgramsList;
use App\Models\FccLicense;
use App\Models\FccPoliticalFileEntry;
use App\Models\FccPublicFileDocument;
use App\Models\FccRegulatoryFee;
use App\Models\FccStationLogEntry;
use App\Models\FccTowerLightingInspection;
use App\Models\FccTowerLightingOutage;
use Illuminate\Database\Seeder;

/**
 * Seeds operational FCC compliance data: EAS tests, public-file documents,
 * Issues/Programs lists, ASRs, tower lighting inspections, political file
 * entries, station log entries, regulatory fees, and form filings.
 *
 * The data is illustrative for demo use but realistic enough that an FCC
 * compliance officer would recognize the workflow and required fields.
 */
class FccOperationalSeeder extends Seeder
{
    public function run(): void
    {
        // After fcc:import-bulk we may have ~30K stations. Generating
        // operational records for every station would create millions of
        // rows. Pick a representative sample so the dashboard renders
        // realistic compliance activity without bloating the DB.
        //
        // Strategy: prefer well-known reference stations (the ones we
        // pull live via fcc:import) plus a random tail.
        $referenceCalls = [
            'WABC', 'WTOP', 'KCBS-FM', 'WGBH-FM', 'KQED-FM', 'WAMU',
            'KCRW', 'KUOW', 'WHTZ', 'KFI', 'WBZ', 'KFI-AM', 'WBZ-AM',
            'WABC-AM', 'KQED', 'WGBH',
        ];

        $referenced = FccLicense::query()
            ->whereIn('call_sign', $referenceCalls)
            ->get();

        $extraSample = FccLicense::query()
            ->whereNotIn('call_sign', $referenceCalls)
            ->inRandomOrder()
            ->limit(30 - $referenced->count())
            ->get();

        $licenses = $referenced->merge($extraSample)->keyBy('call_sign');
        if ($licenses->isEmpty()) {
            return;
        }

        $this->command?->getOutput()?->writeln(
            "  Seeding operational data for ".$licenses->count()." sample stations"
        );

        // ---- ASR registrations (one per tower-bearing facility) ----
        $asrs = [
            ['asr_number' => '1011234', 'owner' => 'Market Hall Broadcasting, LLC', 'structure_type' => 'self_supporting', 'overall_height_meters' => 308.0, 'lighting_type' => 'medium_dual', 'painting_required' => 'aviation_orange_white', 'latitude' => 32.7767, 'longitude' => -96.7970],
            ['asr_number' => '1023456', 'owner' => 'Pioneer Media Group',           'structure_type' => 'guyed',            'overall_height_meters' => 142.0, 'lighting_type' => 'red_only',    'painting_required' => 'aviation_orange_white', 'latitude' => 42.3601, 'longitude' => -71.0589],
            ['asr_number' => '1009876', 'owner' => 'CityView Television, Inc.',     'structure_type' => 'self_supporting', 'overall_height_meters' => 922.0, 'lighting_type' => 'high_dual',   'painting_required' => 'aviation_orange_white', 'latitude' => 34.2257, 'longitude' => -118.0586],
            ['asr_number' => '1234567', 'owner' => 'North County Radio',            'structure_type' => 'monopole',        'overall_height_meters' => 215.0, 'lighting_type' => 'medium_dual', 'painting_required' => 'none',                  'latitude' => 32.7157, 'longitude' => -117.1611],
            ['asr_number' => '1099877', 'owner' => 'Metro Media Group',             'structure_type' => 'guyed',            'overall_height_meters' => 411.0, 'lighting_type' => 'high_dual',   'painting_required' => 'aviation_orange_white', 'latitude' => 41.8781, 'longitude' => -87.6298],
        ];
        foreach ($asrs as $a) {
            $a['last_inspection_date'] = now()->subMonths(rand(1, 3))->toDateString();
            $a['next_inspection_due'] = now()->addMonths(3)->toDateString();
            $a['faa_study_number'] = '20'.rand(20, 25).'-AWP-'.rand(1000, 9999).'-OE';
            FccAsrRegistration::updateOrCreate(['asr_number' => $a['asr_number']], $a);
        }

        // ---- EAS test logs — last 12 weeks of RWTs + monthly RMTs ----
        foreach ($licenses as $license) {
            // Weekly RWTs for the last 12 weeks
            for ($w = 0; $w < 12; $w++) {
                $when = now()->subWeeks($w)->setTime(rand(8, 17), [0, 15, 30, 45][rand(0, 3)]);
                FccEasTest::firstOrCreate(
                    ['license_id' => $license->id, 'test_datetime' => $when, 'test_type' => 'RWT'],
                    [
                        'direction' => $w % 4 === 0 ? 'originated' : 'received',
                        'originator_code' => 'EAS',
                        'event_code' => 'RWT',
                        'audio_intelligible' => true,
                        'visual_message_present' => true,
                        'logged_by' => $w % 2 === 0 ? 'C. Helstein' : 'A. Reyes',
                    ]
                );
            }
            // Monthly RMTs for the last 6 months
            for ($m = 0; $m < 6; $m++) {
                $when = now()->subMonths($m)->day(rand(5, 25))->setTime(rand(10, 16), 0);
                FccEasTest::firstOrCreate(
                    ['license_id' => $license->id, 'test_datetime' => $when, 'test_type' => 'RMT'],
                    [
                        'direction' => 'received',
                        'originator_code' => 'PEP',
                        'event_code' => 'RMT',
                        'audio_intelligible' => true,
                        'visual_message_present' => true,
                        'filed_in_etrs' => $m === 0,
                        'etrs_filed_date' => $m === 0 ? now()->subDays(rand(5, 25))->toDateString() : null,
                        'logged_by' => 'C. Helstein',
                    ]
                );
            }
        }

        // ---- Issues/Programs Lists — last 4 quarters per license ----
        $issueBank = [
            'Mental Health Awareness', 'Local Economy & Jobs', 'Public Safety',
            'Housing & Affordability', 'Education', 'Infrastructure',
            'Senior Services', 'Veteran Affairs', 'Childcare & Youth',
            'Wildfire & Emergency Preparedness', 'Climate & Environment',
        ];
        foreach ($licenses as $license) {
            for ($q = 0; $q < 4; $q++) {
                $date = now()->subMonths($q * 3);
                $year = $date->year;
                $quarter = 'Q'.ceil($date->month / 3);

                $list = FccIssuesProgramsList::firstOrCreate(
                    ['license_id' => $license->id, 'quarter_year' => $year, 'quarter' => $quarter],
                    [
                        'placed_in_file_date' => $q === 0 ? null : $date->endOfQuarter()->addDays(rand(3, 9))->toDateString(),
                        'status' => $q === 0 ? 'draft' : 'placed_in_file',
                    ]
                );

                if ($list->entries()->exists()) continue;

                $picked = collect($issueBank)->shuffle()->take(5);
                foreach ($picked as $issue) {
                    FccIssuesProgramsEntry::create([
                        'list_id' => $list->id,
                        'issue' => $issue,
                        'program_title' => $issue.' — Community Spotlight',
                        'program_description' => '15-minute interview segment featuring local leaders and stakeholders addressing '.$issue.'.',
                        'aired_at' => $date->copy()->subDays(rand(1, 80))->setTime(rand(6, 18), 0),
                        'duration_minutes' => [15, 30, 60][rand(0, 2)],
                        'program_type' => ['public_affairs', 'news', 'interview'][rand(0, 2)],
                    ]);
                }
            }
        }

        // ---- Public File Documents ----
        foreach ($licenses as $license) {
            $docs = [
                ['document_type' => 'authorization',              'title' => 'License Authorization — '.$license->call_sign,        'document_date' => $license->grant_date,                              'uploaded_to_lms_date' => $license->grant_date],
                ['document_type' => 'biennial_ownership_report',  'title' => 'Biennial Ownership Report (Form 323)',                'document_date' => now()->subYear()->toDateString(),                  'uploaded_to_lms_date' => now()->subYear()->addDays(2)->toDateString()],
                ['document_type' => 'eeo_public_file',            'title' => 'Annual EEO Public File Report',                       'document_date' => now()->subMonths(2)->toDateString(),               'uploaded_to_lms_date' => now()->subMonths(2)->toDateString()],
                ['document_type' => 'issues_programs_list',       'title' => 'Issues/Programs List — Previous Quarter',             'document_date' => now()->subMonths(3)->endOfQuarter()->toDateString(), 'uploaded_to_lms_date' => now()->subMonths(3)->endOfQuarter()->addDays(7)->toDateString()],
                ['document_type' => 'contour_map',                'title' => 'Service Contour Map (60 dBu / 54 dBu)',               'document_date' => $license->grant_date],
            ];
            foreach ($docs as $d) {
                $d['license_id'] = $license->id;
                $d['retention_until'] = $license->expiration_date;
                $d['uploaded_by'] = 'C. Helstein';
                $d['lms_url'] = 'https://publicfiles.fcc.gov/'.strtolower($license->call_sign).'/'.uniqid();
                FccPublicFileDocument::firstOrCreate(
                    ['license_id' => $license->id, 'document_type' => $d['document_type'], 'title' => $d['title']],
                    $d
                );
            }
        }

        // ---- Tower Lighting Inspections — quarterly per ASR ----
        $facilityIdsByAsr = [];
        foreach (FccAsrRegistration::all() as $asr) {
            for ($q = 0; $q < 4; $q++) {
                $insp = $asr->last_inspection_date
                    ? \Illuminate\Support\Carbon::parse($asr->last_inspection_date)->subMonths($q * 3)
                    : now()->subMonths($q * 3);
                FccTowerLightingInspection::firstOrCreate(
                    ['asr_registration_id' => $asr->id, 'inspection_date' => $insp->toDateString()],
                    [
                        'inspector_name' => 'C. Helstein, CPBE',
                        'result' => $q === 0 && $asr->asr_number === '1234567' ? 'minor_issue' : 'operational',
                        'automatic_monitor_observed' => true,
                        'manual_observation_performed' => true,
                        'next_inspection_due' => $insp->copy()->addMonths(3)->toDateString(),
                        'findings' => $q === 0 && $asr->asr_number === '1234567'
                            ? 'L-810 side marker on south leg observed flickering during nightly auto-test.'
                            : 'All beacons and side markers operating per FAA Style A spec.',
                    ]
                );
            }
        }

        // One outage on the North County tower
        $ncAsr = FccAsrRegistration::where('asr_number', '1234567')->first();
        if ($ncAsr) {
            FccTowerLightingOutage::firstOrCreate(
                ['asr_registration_id' => $ncAsr->id, 'outage_observed_at' => now()->subDays(9)->setTime(2, 14)],
                [
                    'faa_notified_at' => now()->subDays(9)->setTime(2, 47),
                    'notam_number' => '!SAN 06/' . str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT),
                    'repaired_at' => now()->subDays(7)->setTime(11, 0),
                    'faa_cancellation_at' => now()->subDays(7)->setTime(11, 22),
                    'failure_type' => 'side_marker',
                    'cause' => 'L-810 LED module degraded; replaced under spare-parts inventory.',
                    'actions_taken' => 'NOTAM issued through Lockheed Flight Service. Tower crew dispatched next business day.',
                ]
            );
        }

        // ---- Political File Entries (focused on stations in election windows) ----
        $political = [
            ['call' => 'KXYZ-FM', 'candidate' => 'Sarah Chen for U.S. Senate (TX-D)',     'sponsor' => 'Chen for Senate',           'office' => 'federal_senate',  'spots' => 220, 'rate' => 185.00],
            ['call' => 'WABC-AM', 'candidate' => 'Friends of Marcus Hill (R-MA)',          'sponsor' => 'Marcus Hill Committee',     'office' => 'federal_house',   'spots' => 140, 'rate' => 95.00],
            ['call' => 'WQRS-TV', 'candidate' => 'CA Proposition 19 — Yes',                'sponsor' => 'Yes on 19 Coalition',       'office' => 'ballot_initiative','spots' => 60,  'rate' => 1450.00],
            ['call' => 'KDEF-FM', 'candidate' => 'San Diego City Council Dist. 3 — Park',  'sponsor' => 'Park for Council',          'office' => 'local',           'spots' => 75,  'rate' => 42.00],
            ['call' => 'KJKL-TV', 'candidate' => 'IL Gubernatorial — Rivera (D)',          'sponsor' => 'Rivera for Illinois',       'office' => 'state_governor',  'spots' => 90,  'rate' => 980.00],
        ];
        foreach ($political as $p) {
            $license = $licenses[$p['call']] ?? null;
            if (! $license) continue;
            FccPoliticalFileEntry::firstOrCreate(
                ['license_id' => $license->id, 'candidate_or_issue' => $p['candidate'], 'order_date' => now()->subDays(rand(5, 35))->toDateString()],
                [
                    'sponsor' => $p['sponsor'],
                    'office' => $p['office'],
                    'flight_start_date' => now()->subDays(rand(0, 7))->toDateString(),
                    'flight_end_date' => now()->addDays(rand(7, 30))->toDateString(),
                    'spots_purchased' => $p['spots'],
                    'rate_per_spot' => $p['rate'],
                    'total_amount' => round($p['spots'] * $p['rate'], 2),
                    'lowest_unit_rate_window' => true,
                    'uploaded_to_public_file_date' => now()->subDays(rand(0, 5))->toDateString(),
                ]
            );
        }

        // ---- Station log entries — recent transmitter readings + EAS + tower ----
        foreach ($licenses as $license) {
            for ($d = 0; $d < 7; $d++) {
                FccStationLogEntry::create([
                    'license_id' => $license->id,
                    'logged_at' => now()->subDays($d)->setTime(8, 0),
                    'entry_type' => 'transmitter_reading',
                    'summary' => 'Morning transmitter readings logged.',
                    'readings' => [
                        'plate_voltage' => rand(7800, 8200),
                        'plate_current' => round(rand(2200, 2600) / 1000, 2),
                        'forward_power_kw' => round(rand(900, 1010) / 100, 2),
                        'reflected_power_w' => rand(15, 95),
                        'swr' => round(1 + rand(2, 12) / 100, 2),
                    ],
                    'logged_by' => 'C. Helstein',
                ]);
            }
            FccStationLogEntry::create([
                'license_id' => $license->id,
                'logged_at' => now()->subDays(2)->setTime(14, 30),
                'entry_type' => 'tower_lighting_check',
                'summary' => 'Auto-monitor confirmed all beacons operational at sunset transition.',
                'logged_by' => 'C. Helstein',
            ]);
        }

        // ---- Regulatory fees — current FY ----
        $feeMap = [
            'AM' => ['cat' => 'AM Class B', 'amt' => 1075.00],
            'FM' => ['cat' => 'FM Class C / C0', 'amt' => 4500.00],
            'TV' => ['cat' => 'TV Markets 1-10 (UHF)', 'amt' => 17800.00],
            'LPFM' => ['cat' => 'LPFM', 'amt' => 535.00],
            'LPTV' => ['cat' => 'LPTV / TV Translator', 'amt' => 535.00],
        ];
        foreach ($licenses as $license) {
            $row = $feeMap[$license->service] ?? ['cat' => 'Other Broadcast', 'amt' => 1000.00];
            FccRegulatoryFee::firstOrCreate(
                ['license_id' => $license->id, 'fiscal_year' => now()->year],
                [
                    'fee_category' => $row['cat'],
                    'amount_due' => $row['amt'],
                    'amount_paid' => $license->status === 'non_compliant' ? 0 : $row['amt'],
                    'due_date' => now()->month <= 9 ? now()->setMonth(9)->setDay(28)->toDateString() : now()->addYear()->setMonth(9)->setDay(28)->toDateString(),
                    'paid_date' => $license->status === 'non_compliant' ? null : now()->subMonths(rand(1, 6))->toDateString(),
                    'confirmation_number' => $license->status === 'non_compliant' ? null : 'PG-'.strtoupper(substr(md5($license->call_sign), 0, 9)),
                    'status' => $license->status === 'non_compliant' ? 'overdue' : 'paid',
                ]
            );
        }

        // ---- Recent form filings ----
        foreach ($licenses as $license) {
            FccFormFiling::firstOrCreate(
                ['license_id' => $license->id, 'form_number' => '323', 'filed_date' => now()->subYear()->toDateString()],
                [
                    'form_title' => 'Biennial Ownership Report (Commercial)',
                    'file_number' => '00000'.rand(10000, 99999).'-'.rand(100, 999),
                    'status' => 'granted',
                    'filed_by' => 'C. Helstein',
                ]
            );
            FccFormFiling::firstOrCreate(
                ['license_id' => $license->id, 'form_number' => '397', 'filed_date' => now()->subMonths(4)->toDateString()],
                [
                    'form_title' => 'EEO Annual Report',
                    'status' => 'filed',
                    'filed_by' => 'C. Helstein',
                ]
            );
        }
    }
}
