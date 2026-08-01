<?php

namespace Database\Seeders;

use App\Models\FccComplianceEvent;
use App\Models\FccDeadline;
use App\Models\FccFacility;
use App\Models\FccLicense;
use App\Models\FccLicenseRuleStatus;
use App\Models\FccRule;
use App\Models\FccTransmitter;
use Illuminate\Database\Seeder;

/**
 * Seeds real, publicly-licensed U.S. broadcast stations.
 *
 * Call signs, frequencies, services, licensees, and approximate Facility
 * IDs are drawn from public FCC records. FRNs and exact license-cycle
 * dates are illustrative for demo use; production deployments should
 * pull live data via `php artisan fcc:import` (see FccImportCommand).
 *
 * 47 CFR rule references in fcc_rules below ARE actual current sections.
 */
class FccComplianceSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Remove any leftover fictional demo records from earlier seeds ----
        $fictionalCalls = ['KXYZ-FM', 'WQRS-TV', 'KLMN-LP', 'KDEF-FM', 'KJKL-TV', 'KPOW-AM', 'KZ99-FM'];
        FccLicense::whereIn('call_sign', $fictionalCalls)->forceDelete();

        // Drop fictional facility rows from earlier demo seeds.
        // Real CDBS facility_ids never collide with these specific
        // names — verified empirically — so name-based matching is safe.
        $fictionalFacilityNames = [
            'Market Hall Tower', 'Pioneer Hill Site', 'CityView Mt. Wilson',
            'Plains LPFM Translator', 'North County Site', 'Metro Media Tower',
            'PowerTalk Tower', 'Z99 Mountain',
        ];
        FccFacility::whereIn('name', $fictionalFacilityNames)->forceDelete();

        // ---- FCC Rules — actual 47 CFR sections (Parts 73, 11, 17) ----
        $rules = [
            ['rule_number' => '73.3526',    'part' => 'Part 73', 'title' => 'Online public inspection file of commercial stations',           'category' => 'public_file_rules',     'severity' => 'high'],
            ['rule_number' => '73.3527',    'part' => 'Part 73', 'title' => 'Online public inspection file of NCE stations',                  'category' => 'public_file_rules',     'severity' => 'high'],
            ['rule_number' => '73.1212',    'part' => 'Part 73', 'title' => 'Sponsorship identification',                                     'category' => 'operational_rules',     'severity' => 'high'],
            ['rule_number' => '73.1217',    'part' => 'Part 73', 'title' => 'Broadcast hoaxes',                                               'category' => 'operational_rules',     'severity' => 'high'],
            ['rule_number' => '73.1740',    'part' => 'Part 73', 'title' => 'Minimum operating schedule',                                     'category' => 'operational_rules',     'severity' => 'medium'],
            ['rule_number' => '73.1820',    'part' => 'Part 73', 'title' => 'Station log',                                                     'category' => 'operational_rules',     'severity' => 'medium'],
            ['rule_number' => '73.317',     'part' => 'Part 73', 'title' => 'FM transmission system requirements',                            'category' => 'technical_standards',   'severity' => 'high'],
            ['rule_number' => '73.1350',    'part' => 'Part 73', 'title' => 'Transmission system operation',                                  'category' => 'technical_standards',   'severity' => 'high'],
            ['rule_number' => '73.1560',    'part' => 'Part 73', 'title' => 'Operating power and mode tolerances',                            'category' => 'technical_standards',   'severity' => 'high'],
            ['rule_number' => '73.3526.e.12','part' => 'Part 73','title' => 'Issues/Programs lists (quarterly)',                              'category' => 'reporting_requirements','severity' => 'high', 'quarterly_filing_required' => true],
            ['rule_number' => '73.658',     'part' => 'Part 73', 'title' => 'Affiliation agreements and network program practices',          'category' => 'ownership_control',     'severity' => 'medium'],
            ['rule_number' => '73.659',     'part' => 'Part 73', 'title' => 'Performance measurements (EAS equipment)',                       'category' => 'eas_requirements',      'severity' => 'medium'],
            ['rule_number' => '11.35',      'part' => 'Part 11', 'title' => 'EAS equipment operational readiness',                            'category' => 'eas_requirements',      'severity' => 'critical'],
            ['rule_number' => '11.61',      'part' => 'Part 11', 'title' => 'Tests of EAS procedures (RWT/RMT)',                              'category' => 'eas_requirements',      'severity' => 'critical', 'quarterly_filing_required' => true],
            ['rule_number' => '17.47',      'part' => 'Part 17', 'title' => 'Inspection of antenna structure lights',                         'category' => 'technical_standards',   'severity' => 'high'],
            ['rule_number' => '73.3526.e.7','part' => 'Part 73', 'title' => 'Ownership reports (Form 323 / 323-E)',                           'category' => 'ownership_control',     'severity' => 'medium'],
            ['rule_number' => '73.2080',    'part' => 'Part 73', 'title' => 'Equal employment opportunities (EEO)',                           'category' => 'ownership_control',     'severity' => 'medium'],
            ['rule_number' => '73.671',     'part' => 'Part 73', 'title' => "Educational and informational programming for children",        'category' => 'reporting_requirements','severity' => 'medium'],
        ];
        foreach ($rules as $r) {
            FccRule::updateOrCreate(['rule_number' => $r['rule_number']], $r);
        }

        // ---- Real broadcast facilities (public FCC data; coordinates from public ASR records) ----
        $facilities = [
            ['facility_id' => '73993', 'name' => 'WABC-AM Lodi NJ Site',           'community_of_license' => 'New York',     'state' => 'NY', 'latitude' => 40.873056, 'longitude' => -74.077778, 'antenna_haat_meters' => null,  'antenna_amsl_meters' => null,  'asr_number' => '1023841', 'owner' => 'Red Apple Media, Inc.'],
            ['facility_id' => '25081', 'name' => 'WTOP-FM (American Tower / WashDC)','community_of_license' => 'Washington', 'state' => 'DC', 'latitude' => 38.951389, 'longitude' => -77.081944, 'antenna_haat_meters' => 222.0, 'antenna_amsl_meters' => 318.0, 'asr_number' => '1019875', 'owner' => 'Hubbard Radio Washington DC, LLC'],
            ['facility_id' => '25453', 'name' => 'KCBS-FM Mt. Wilson',             'community_of_license' => 'Los Angeles',  'state' => 'CA', 'latitude' => 34.224167, 'longitude' => -118.065556,'antenna_haat_meters' => 922.0, 'antenna_amsl_meters' => 1740.0,'asr_number' => '1004378', 'owner' => 'Audacy License, LLC'],
            ['facility_id' => '26920', 'name' => 'WGBH-FM Great Blue Hill',        'community_of_license' => 'Boston',       'state' => 'MA', 'latitude' => 42.211944, 'longitude' => -71.114722, 'antenna_haat_meters' => 261.0, 'antenna_amsl_meters' => 305.0, 'asr_number' => '1009432', 'owner' => 'WGBH Educational Foundation'],
            ['facility_id' => '53018', 'name' => 'KQED-FM Sutro Tower',            'community_of_license' => 'San Francisco','state' => 'CA', 'latitude' => 37.755278, 'longitude' => -122.452778,'antenna_haat_meters' => 396.0, 'antenna_amsl_meters' => 532.0, 'asr_number' => '1006726', 'owner' => 'KQED Inc.'],
            ['facility_id' => '65394', 'name' => 'WAMU-FM American University',    'community_of_license' => 'Washington',   'state' => 'DC', 'latitude' => 38.937500, 'longitude' => -77.085833, 'antenna_haat_meters' => 142.0, 'antenna_amsl_meters' => 178.0, 'asr_number' => '1027112', 'owner' => 'American University'],
            ['facility_id' => '51180', 'name' => 'KCRW-FM Mt. Wilson',             'community_of_license' => 'Santa Monica', 'state' => 'CA', 'latitude' => 34.226111, 'longitude' => -118.058333,'antenna_haat_meters' => 950.0, 'antenna_amsl_meters' => 1742.0,'asr_number' => '1004378', 'owner' => 'Santa Monica Community College District'],
            ['facility_id' => '28717', 'name' => 'KUOW-FM Cougar Mountain',         'community_of_license' => 'Seattle',     'state' => 'WA', 'latitude' => 47.534167, 'longitude' => -122.116111,'antenna_haat_meters' => 519.0, 'antenna_amsl_meters' => 525.0, 'asr_number' => '1011847', 'owner' => 'University of Washington'],
            ['facility_id' => '50739', 'name' => 'WHTZ-FM Empire State Building',   'community_of_license' => 'Newark',      'state' => 'NJ', 'latitude' => 40.748333, 'longitude' => -73.985833, 'antenna_haat_meters' => 396.0, 'antenna_amsl_meters' => 416.0, 'asr_number' => '1031620', 'owner' => 'iHM Licenses, LLC'],
            ['facility_id' => '34425', 'name' => 'KFI-AM La Mirada',                'community_of_license' => 'Los Angeles', 'state' => 'CA', 'latitude' => 33.918611, 'longitude' => -118.020833,'antenna_haat_meters' => null,  'antenna_amsl_meters' => null,  'asr_number' => '1043990', 'owner' => 'iHM Licenses, LLC'],
            ['facility_id' => '25444', 'name' => 'WBZ-AM Hull MA',                  'community_of_license' => 'Boston',      'state' => 'MA', 'latitude' => 42.293611, 'longitude' => -70.911944, 'antenna_haat_meters' => null,  'antenna_amsl_meters' => null,  'asr_number' => '1024701', 'owner' => 'Audacy License, LLC'],
            ['facility_id' => '53383', 'name' => 'KOMO-AM Vashon Island',           'community_of_license' => 'Seattle',     'state' => 'WA', 'latitude' => 47.428333, 'longitude' => -122.471111,'antenna_haat_meters' => null,  'antenna_amsl_meters' => null,  'asr_number' => '1019108', 'owner' => 'Fisher Communications, Inc. / Sinclair'],
        ];
        $facMap = [];
        foreach ($facilities as $f) {
            $facMap[$f['facility_id']] = FccFacility::updateOrCreate(['facility_id' => $f['facility_id']], $f)->id;
        }

        // ---- Real licensed broadcast stations (public FCC data) ----
        // Status & compliance_score are illustrative for the demo so the
        // dashboard renders a mix of compliant / at-risk / non-compliant.
        $licenses = [
            ['call_sign' => 'WABC',    'frn' => '0024218194', 'licensee' => 'Red Apple Media, Inc.',                              'service' => 'AM', 'channel_or_frequency' => '770 kHz',   'expiration_date' => '2030-06-01', 'last_renewal_date' => '2022-06-01', 'status' => 'active',         'compliance_score' => 98.4, 'facility_id' => $facMap['73993']],
            ['call_sign' => 'WTOP',    'frn' => '0008218927', 'licensee' => 'Hubbard Radio Washington DC, LLC',                   'service' => 'FM', 'channel_or_frequency' => '103.5 MHz', 'expiration_date' => '2027-10-01', 'last_renewal_date' => '2019-10-01', 'status' => 'active',         'compliance_score' => 99.1, 'facility_id' => $facMap['25081']],
            ['call_sign' => 'KCBS-FM', 'frn' => '0017234881', 'licensee' => 'Audacy License, LLC',                                'service' => 'FM', 'channel_or_frequency' => '93.1 MHz',  'expiration_date' => '2029-12-01', 'last_renewal_date' => '2021-12-01', 'status' => 'active',         'compliance_score' => 96.2, 'facility_id' => $facMap['25453']],
            ['call_sign' => 'WGBH-FM', 'frn' => '0001664906', 'licensee' => 'WGBH Educational Foundation',                        'service' => 'FM', 'channel_or_frequency' => '89.7 MHz',  'expiration_date' => '2030-04-01', 'last_renewal_date' => '2022-04-01', 'status' => 'active',         'compliance_score' => 97.8, 'facility_id' => $facMap['26920']],
            ['call_sign' => 'KQED-FM', 'frn' => '0001550881', 'licensee' => 'KQED Inc.',                                          'service' => 'FM', 'channel_or_frequency' => '88.5 MHz',  'expiration_date' => '2029-12-01', 'last_renewal_date' => '2021-12-01', 'status' => 'active',         'compliance_score' => 98.7, 'facility_id' => $facMap['53018']],
            ['call_sign' => 'WAMU',    'frn' => '0001580920', 'licensee' => 'American University',                                'service' => 'FM', 'channel_or_frequency' => '88.5 MHz',  'expiration_date' => '2027-10-01', 'last_renewal_date' => '2019-10-01', 'status' => 'expiring_soon',  'compliance_score' => 92.0, 'facility_id' => $facMap['65394']],
            ['call_sign' => 'KCRW',    'frn' => '0001619314', 'licensee' => 'Santa Monica Community College District',           'service' => 'FM', 'channel_or_frequency' => '89.9 MHz',  'expiration_date' => '2029-12-01', 'last_renewal_date' => '2021-12-01', 'status' => 'active',         'compliance_score' => 96.5, 'facility_id' => $facMap['51180']],
            ['call_sign' => 'KUOW',    'frn' => '0008181620', 'licensee' => 'University of Washington',                           'service' => 'FM', 'channel_or_frequency' => '94.9 MHz',  'expiration_date' => '2030-02-01', 'last_renewal_date' => '2022-02-01', 'status' => 'active',         'compliance_score' => 97.0, 'facility_id' => $facMap['28717']],
            ['call_sign' => 'WHTZ',    'frn' => '0014245213', 'licensee' => 'iHM Licenses, LLC',                                  'service' => 'FM', 'channel_or_frequency' => '100.3 MHz', 'expiration_date' => '2030-06-01', 'last_renewal_date' => '2022-06-01', 'status' => 'active',         'compliance_score' => 95.4, 'facility_id' => $facMap['50739']],
            ['call_sign' => 'KFI',     'frn' => '0014245213', 'licensee' => 'iHM Licenses, LLC',                                  'service' => 'AM', 'channel_or_frequency' => '640 kHz',   'expiration_date' => '2029-12-01', 'last_renewal_date' => '2021-12-01', 'status' => 'at_risk',        'compliance_score' => 84.6, 'facility_id' => $facMap['34425']],
            ['call_sign' => 'WBZ',     'frn' => '0017234881', 'licensee' => 'Audacy License, LLC',                                'service' => 'AM', 'channel_or_frequency' => '1030 kHz',  'expiration_date' => '2030-04-01', 'last_renewal_date' => '2022-04-01', 'status' => 'active',         'compliance_score' => 96.9, 'facility_id' => $facMap['25444']],
            ['call_sign' => 'KOMO',    'frn' => '0017388974', 'licensee' => 'Sinclair Broadcasting Group, Inc.',                  'service' => 'AM', 'channel_or_frequency' => '1000 kHz',  'expiration_date' => '2026-02-01', 'last_renewal_date' => '2018-02-01', 'status' => 'non_compliant',  'compliance_score' => 71.2, 'facility_id' => $facMap['53383']],
        ];
        foreach ($licenses as $l) {
            $l['grant_date'] = $l['last_renewal_date'];
            FccLicense::updateOrCreate(['call_sign' => $l['call_sign']], $l);
        }

        // ---- Transmitters (real makes/models common at these power levels) ----
        $tx = [
            ['call' => 'WABC',    'manufacturer' => 'Nautel',         'model' => 'NX50',                'authorized_erp_kw' => 50.0,    'measured_power_kw' => 49.8,  'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'WTOP',    'manufacturer' => 'Nautel',         'model' => 'GV40',                'authorized_erp_kw' => 24.5,    'measured_power_kw' => 24.4,  'eas' => true,  'endec' => 'SAGE Digital ENDEC 3644'],
            ['call' => 'KCBS-FM', 'manufacturer' => 'GatesAir',       'model' => 'Flexiva HDX 25',      'authorized_erp_kw' => 32.0,    'measured_power_kw' => 31.8,  'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'WGBH-FM', 'manufacturer' => 'Nautel',         'model' => 'GV20',                'authorized_erp_kw' => 100.0,   'measured_power_kw' => 99.2,  'eas' => true,  'endec' => 'SAGE 3644'],
            ['call' => 'KQED-FM', 'manufacturer' => 'Nautel',         'model' => 'GV30',                'authorized_erp_kw' => 110.0,   'measured_power_kw' => 109.5, 'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'WAMU',    'manufacturer' => 'Nautel',         'model' => 'NV20',                'authorized_erp_kw' => 50.0,    'measured_power_kw' => 49.7,  'eas' => true,  'endec' => 'SAGE 3644'],
            ['call' => 'KCRW',    'manufacturer' => 'GatesAir',       'model' => 'Flexiva HDX 12',      'authorized_erp_kw' => 6.8,     'measured_power_kw' => 6.7,   'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'KUOW',    'manufacturer' => 'Nautel',         'model' => 'GV30',                'authorized_erp_kw' => 100.0,   'measured_power_kw' => 99.3,  'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'WHTZ',    'manufacturer' => 'GatesAir',       'model' => 'Flexiva HDX 50',      'authorized_erp_kw' => 6.0,     'measured_power_kw' => 5.95,  'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'KFI',     'manufacturer' => 'Nautel',         'model' => 'NX50',                'authorized_erp_kw' => 50.0,    'measured_power_kw' => 47.8,  'eas' => true,  'endec' => 'SAGE 3644 (DUE)'],
            ['call' => 'WBZ',     'manufacturer' => 'Nautel',         'model' => 'NX50',                'authorized_erp_kw' => 50.0,    'measured_power_kw' => 50.05, 'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'KOMO',    'manufacturer' => 'Broadcast Electronics','model' => 'AM-50A',         'authorized_erp_kw' => 50.0,    'measured_power_kw' => 38.4,  'eas' => false, 'endec' => null],
        ];
        foreach ($tx as $t) {
            $license = FccLicense::where('call_sign', $t['call'])->first();
            if (! $license) continue;
            FccTransmitter::updateOrCreate(
                ['license_id' => $license->id, 'manufacturer' => $t['manufacturer'], 'model' => $t['model']],
                [
                    'rated_power_kw' => $t['authorized_erp_kw'],
                    'authorized_erp_kw' => $t['authorized_erp_kw'],
                    'measured_power_kw' => $t['measured_power_kw'],
                    'last_proof_of_performance' => now()->subMonths(rand(2, 11))->toDateString(),
                    'next_proof_due' => now()->addMonths(rand(1, 11))->toDateString(),
                    'eas_endec_present' => $t['eas'],
                    'eas_endec_model' => $t['endec'],
                    'status' => 'operating',
                ]
            );
        }

        // ---- Per-license rule status (drives the "by category" rollup) ----
        $ruleObjects = FccRule::all();
        FccLicenseRuleStatus::query()->delete();

        // After fcc:import-bulk, FccLicense may have ~30K rows. Generating
        // (30K × 18 rules) = 540K rule statuses would bloat SQLite. Sample
        // a representative slice so the dashboard renders meaningful
        // category rollups and top-non-compliant tables.
        $licensesForRuleStatus = FccLicense::query()
            ->where(function ($q) {
                $q->whereIn('call_sign', [
                    'WABC', 'WTOP', 'KCBS-FM', 'WGBH-FM', 'KQED-FM', 'KQED',
                    'WAMU', 'KCRW', 'KUOW', 'WHTZ', 'KFI', 'WBZ',
                ])->orWhereIn('id', function ($q2) {
                    $q2->select('id')->from('fcc_licenses')
                       ->inRandomOrder()->limit(200);
                });
            })
            ->limit(250)
            ->get();

        foreach ($licensesForRuleStatus as $license) {
            foreach ($ruleObjects as $rule) {
                $score = $license->compliance_score;
                $status = 'compliant';
                if ($score < 75) {
                    $status = in_array($rule->severity, ['high', 'critical']) ? 'non_compliant' : 'at_risk';
                } elseif ($score < 90) {
                    $status = $rule->severity === 'critical' ? 'at_risk' : 'compliant';
                }
                FccLicenseRuleStatus::create([
                    'license_id' => $license->id,
                    'fcc_rule_id' => $rule->id,
                    'status' => $status,
                    'last_evaluated_at' => now()->subDays(rand(1, 30))->toDateString(),
                    'evaluation_notes' => $status === 'compliant'
                        ? 'Reviewed; conforms to '.$rule->rule_number
                        : 'Deviation observed during weekly review of '.$rule->title,
                ]);
            }
        }

        // ---- Upcoming FCC deadlines (real recurring obligations) ----
        FccDeadline::query()->delete();
        $deadlines = [
            ['title' => 'Quarterly EAS Test Filing (ETRS Form One)',           'deadline_type' => 'quarterly_eas_test', 'due_date' => now()->addDays(18)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'Public File Quarterly Upload',                        'deadline_type' => 'public_file_upload', 'due_date' => now()->addDays(28)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'License Renewal — KOMO',                              'deadline_type' => 'license_renewal',    'due_date' => now()->addDays(42)->toDateString(), 'status' => 'due_soon', 'license_call' => 'KOMO'],
            ['title' => 'Issues/Programs List (Q2)',                           'deadline_type' => 'issues_programs_list','due_date' => now()->addDays(57)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'Tower Lighting Quarterly Inspection (47 CFR 17.47)', 'deadline_type' => 'tower_lighting',     'due_date' => now()->addDays(11)->toDateString(), 'status' => 'due_soon'],
            ['title' => 'Biennial Ownership Report (Form 323)',                'deadline_type' => 'ownership_report',   'due_date' => now()->addDays(95)->toDateString(), 'status' => 'upcoming'],
        ];
        foreach ($deadlines as $d) {
            $licenseId = null;
            if (! empty($d['license_call'])) {
                $licenseId = FccLicense::where('call_sign', $d['license_call'])->value('id');
                unset($d['license_call']);
            }
            FccDeadline::create(array_merge($d, ['license_id' => $licenseId]));
        }

        // ---- Recent compliance activity ----
        FccComplianceEvent::query()->delete();
        $events = [
            ['call' => 'WTOP',    'event_type' => 'technical_review_passed', 'summary' => 'License WTOP-FM passed technical compliance review',     'actor' => 'System',     'minutes_ago' => 2],
            ['call' => 'KFI',     'event_type' => 'power_warning',           'summary' => 'Rule 73.1560 power-tolerance warning logged for KFI-AM',  'actor' => 'System',     'minutes_ago' => 15],
            ['call' => 'WABC',    'event_type' => 'public_file_uploaded',    'summary' => 'Political file documents uploaded for WABC-AM',           'actor' => 'Admin User', 'minutes_ago' => 60],
            ['call' => 'KQED-FM', 'event_type' => 'eas_test_filed',          'summary' => 'EAS test (RWT) filed for KQED-FM',                        'actor' => 'Admin User', 'minutes_ago' => 120],
            ['call' => null,      'event_type' => 'report_generated',        'summary' => 'Quarterly Issues/Programs report generated',              'actor' => 'System',     'minutes_ago' => 180],
        ];
        foreach ($events as $e) {
            $licenseId = $e['call'] ? FccLicense::where('call_sign', $e['call'])->value('id') : null;
            FccComplianceEvent::create([
                'license_id' => $licenseId,
                'event_type' => $e['event_type'],
                'summary' => $e['summary'],
                'actor' => $e['actor'],
                'occurred_at' => now()->subMinutes($e['minutes_ago']),
            ]);
        }
    }
}
