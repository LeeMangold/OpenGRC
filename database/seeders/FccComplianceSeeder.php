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
 * Seeds realistic FCC broadcast-compliance data for a working demo
 * matching the OpenGRC FCC Compliance dashboard. Stations, FRNs, and
 * CFR section numbers below are illustrative for demo use.
 */
class FccComplianceSeeder extends Seeder
{
    public function run(): void
    {
        // ---- FCC Rules (real CFR sections from 47 CFR Parts 73 & 11) ----
        $rules = [
            ['rule_number' => '73.3526', 'part' => 'Part 73', 'title' => 'Online public inspection file of commercial stations', 'category' => 'public_file_rules', 'severity' => 'high'],
            ['rule_number' => '73.3527', 'part' => 'Part 73', 'title' => 'Online public inspection file of noncommercial educational stations', 'category' => 'public_file_rules', 'severity' => 'high'],
            ['rule_number' => '73.1212', 'part' => 'Part 73', 'title' => 'Sponsorship identification', 'category' => 'operational_rules', 'severity' => 'high'],
            ['rule_number' => '73.1217', 'part' => 'Part 73', 'title' => 'Broadcast hoaxes', 'category' => 'operational_rules', 'severity' => 'high'],
            ['rule_number' => '73.1740', 'part' => 'Part 73', 'title' => 'Minimum operating schedule', 'category' => 'operational_rules', 'severity' => 'medium'],
            ['rule_number' => '73.1820', 'part' => 'Part 73', 'title' => 'Station log', 'category' => 'operational_rules', 'severity' => 'medium'],
            ['rule_number' => '73.317', 'part' => 'Part 73', 'title' => 'FM transmission system requirements (identification)', 'category' => 'technical_standards', 'severity' => 'high'],
            ['rule_number' => '73.1350', 'part' => 'Part 73', 'title' => 'Transmission system operation', 'category' => 'technical_standards', 'severity' => 'high'],
            ['rule_number' => '73.1560', 'part' => 'Part 73', 'title' => 'Operating power and mode tolerances', 'category' => 'technical_standards', 'severity' => 'high'],
            ['rule_number' => '73.3526.e.12', 'part' => 'Part 73', 'title' => 'Issues/Programs lists (quarterly)', 'category' => 'reporting_requirements', 'severity' => 'high', 'quarterly_filing_required' => true],
            ['rule_number' => '73.658', 'part' => 'Part 73', 'title' => 'Affiliation agreements and network program practices', 'category' => 'ownership_control', 'severity' => 'medium'],
            ['rule_number' => '73.659', 'part' => 'Part 73', 'title' => 'Performance measurements (EAS equipment)', 'category' => 'eas_requirements', 'severity' => 'medium'],
            ['rule_number' => '11.35', 'part' => 'Part 11', 'title' => 'EAS equipment operational readiness', 'category' => 'eas_requirements', 'severity' => 'critical'],
            ['rule_number' => '11.61', 'part' => 'Part 11', 'title' => 'Tests of EAS procedures (RWT/RMT)', 'category' => 'eas_requirements', 'severity' => 'critical', 'quarterly_filing_required' => true],
            ['rule_number' => '17.47', 'part' => 'Part 17', 'title' => 'Inspection of antenna structure lights', 'category' => 'technical_standards', 'severity' => 'high'],
            ['rule_number' => '73.3526.e.7', 'part' => 'Part 73', 'title' => 'Ownership reports (Form 323 / 323-E)', 'category' => 'ownership_control', 'severity' => 'medium'],
            ['rule_number' => '73.2080', 'part' => 'Part 73', 'title' => 'Equal employment opportunities (EEO)', 'category' => 'ownership_control', 'severity' => 'medium'],
            ['rule_number' => '73.671', 'part' => 'Part 73', 'title' => "Educational and informational programming for children", 'category' => 'reporting_requirements', 'severity' => 'medium'],
        ];
        foreach ($rules as $r) {
            FccRule::updateOrCreate(['rule_number' => $r['rule_number']], $r);
        }

        // ---- Facilities ----
        $facilities = [
            ['facility_id' => '64231', 'name' => 'Market Hall Tower', 'community_of_license' => 'Dallas', 'state' => 'TX', 'latitude' => 32.7767, 'longitude' => -96.7970, 'antenna_haat_meters' => 308.0, 'antenna_amsl_meters' => 472.0, 'asr_number' => '1011234', 'owner' => 'Market Hall Broadcasting, LLC', 'contact_engineer' => 'C. Jensen, CPBE'],
            ['facility_id' => '17421', 'name' => 'Pioneer Hill Site', 'community_of_license' => 'Boston', 'state' => 'MA', 'latitude' => 42.3601, 'longitude' => -71.0589, 'antenna_haat_meters' => 142.0, 'antenna_amsl_meters' => 188.0, 'asr_number' => '1023456', 'owner' => 'Pioneer Media Group', 'contact_engineer' => 'A. Reyes, CBT'],
            ['facility_id' => '50112', 'name' => 'CityView Mt. Wilson', 'community_of_license' => 'Los Angeles', 'state' => 'CA', 'latitude' => 34.2257, 'longitude' => -118.0586, 'antenna_haat_meters' => 922.0, 'antenna_amsl_meters' => 1740.0, 'asr_number' => '1009876', 'owner' => 'CityView Television, Inc.', 'contact_engineer' => 'M. Okafor, CSRE'],
            ['facility_id' => '88210', 'name' => 'Plains LPFM Translator', 'community_of_license' => 'Lincoln', 'state' => 'NE', 'antenna_haat_meters' => 35.0, 'antenna_amsl_meters' => 412.0, 'owner' => 'Voice of the Plains, Inc.', 'contact_engineer' => 'D. Patel'],
            ['facility_id' => '72001', 'name' => 'North County Site', 'community_of_license' => 'San Diego', 'state' => 'CA', 'antenna_haat_meters' => 215.0, 'antenna_amsl_meters' => 312.0, 'asr_number' => '1234567', 'owner' => 'North County Radio Co.'],
            ['facility_id' => '11077', 'name' => 'Metro Media Tower', 'community_of_license' => 'Chicago', 'state' => 'IL', 'antenna_haat_meters' => 411.0, 'antenna_amsl_meters' => 588.0, 'asr_number' => '1099877', 'owner' => 'Metro Media Group'],
            ['facility_id' => '40023', 'name' => 'PowerTalk Tower', 'community_of_license' => 'Phoenix', 'state' => 'AZ', 'antenna_haat_meters' => 88.0, 'antenna_amsl_meters' => 410.0, 'owner' => 'PowerTalk Broadcasting LLC'],
            ['facility_id' => '99077', 'name' => 'Z99 Mountain', 'community_of_license' => 'Reno', 'state' => 'NV', 'antenna_haat_meters' => 420.0, 'antenna_amsl_meters' => 1850.0, 'owner' => 'Z99 LLC'],
        ];
        $facMap = [];
        foreach ($facilities as $f) {
            $facMap[$f['facility_id']] = FccFacility::updateOrCreate(['facility_id' => $f['facility_id']], $f)->id;
        }

        // ---- Licenses (matches the FCC dashboard call signs) ----
        $licenses = [
            ['call_sign' => 'KXYZ-FM', 'frn' => '0001234567', 'licensee' => 'Market Hall Broadcasting, LLC', 'service' => 'FM',   'channel_or_frequency' => '98.7 MHz', 'expiration_date' => '2032-06-15', 'last_renewal_date' => '2024-06-15', 'status' => 'active',         'compliance_score' => 98.7, 'facility_id' => $facMap['64231']],
            ['call_sign' => 'WABC-AM', 'frn' => '0007654321', 'licensee' => 'Pioneer Media Group',         'service' => 'AM',   'channel_or_frequency' => '770 kHz',  'expiration_date' => '2030-07-02', 'last_renewal_date' => '2022-07-02', 'status' => 'active',         'compliance_score' => 97.1, 'facility_id' => $facMap['17421']],
            ['call_sign' => 'WQRS-TV', 'frn' => '0002468013', 'licensee' => 'CityView Television, Inc.',   'service' => 'TV',   'channel_or_frequency' => 'Ch. 27',   'expiration_date' => '2029-07-18', 'last_renewal_date' => '2021-07-18', 'status' => 'active',         'compliance_score' => 95.4, 'facility_id' => $facMap['50112']],
            ['call_sign' => 'KLMN-LP', 'frn' => '0003344556', 'licensee' => 'Voice of the Plains, Inc.',   'service' => 'LPFM', 'channel_or_frequency' => '101.5 MHz','expiration_date' => '2028-08-05', 'last_renewal_date' => '2020-08-05', 'status' => 'active',         'compliance_score' => 94.2, 'facility_id' => $facMap['88210']],
            ['call_sign' => 'KDEF-FM', 'frn' => '0004422110', 'licensee' => 'North County Radio',          'service' => 'FM',   'channel_or_frequency' => '95.3 MHz', 'expiration_date' => '2032-08-22', 'last_renewal_date' => '2024-08-22', 'status' => 'active',         'compliance_score' => 96.8, 'facility_id' => $facMap['72001']],
            ['call_sign' => 'KJKL-TV', 'frn' => '0005566778', 'licensee' => 'Metro Media Group',           'service' => 'TV',   'channel_or_frequency' => 'Ch. 14',   'expiration_date' => '2026-08-30', 'last_renewal_date' => '2018-08-30', 'status' => 'expiring_soon',  'compliance_score' => 89.6, 'facility_id' => $facMap['11077']],
            ['call_sign' => 'KPOW-AM', 'frn' => '0006677889', 'licensee' => 'PowerTalk Radio',             'service' => 'AM',   'channel_or_frequency' => '1340 kHz', 'expiration_date' => '2026-09-14', 'last_renewal_date' => '2018-09-14', 'status' => 'at_risk',        'compliance_score' => 82.3, 'facility_id' => $facMap['40023']],
            ['call_sign' => 'KZ99-FM', 'frn' => '0009988776', 'licensee' => 'Z99 LLC',                     'service' => 'FM',   'channel_or_frequency' => '99.1 MHz', 'expiration_date' => '2026-09-21', 'last_renewal_date' => '2018-09-21', 'status' => 'non_compliant',  'compliance_score' => 61.4, 'facility_id' => $facMap['99077']],
        ];
        foreach ($licenses as $l) {
            $l['grant_date'] = $l['last_renewal_date'];
            FccLicense::updateOrCreate(['call_sign' => $l['call_sign']], $l);
        }

        // ---- Transmitters (one per license) ----
        $tx = [
            ['call' => 'KXYZ-FM', 'manufacturer' => 'Nautel', 'model' => 'GV20',   'authorized_erp_kw' => 50.0,  'measured_power_kw' => 49.7, 'eas' => true,  'endec' => 'SAGE Digital ENDEC 3644'],
            ['call' => 'WABC-AM', 'manufacturer' => 'Nautel', 'model' => 'NX50',   'authorized_erp_kw' => 50.0,  'measured_power_kw' => 50.1, 'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'WQRS-TV', 'manufacturer' => 'GatesAir','model' => 'Maxiva ULXTE-50', 'authorized_erp_kw' => 1000.0, 'measured_power_kw' => 985.0, 'eas' => true, 'endec' => 'Trilithic EASyCAP'],
            ['call' => 'KLMN-LP', 'manufacturer' => 'BW Broadcast', 'model' => 'TX300V2', 'authorized_erp_kw' => 0.1, 'measured_power_kw' => 0.09, 'eas' => true, 'endec' => 'SAGE 3644'],
            ['call' => 'KDEF-FM', 'manufacturer' => 'Nautel', 'model' => 'VS2.5',  'authorized_erp_kw' => 6.0,   'measured_power_kw' => 5.9,  'eas' => true,  'endec' => 'DASDEC-II'],
            ['call' => 'KJKL-TV', 'manufacturer' => 'Hitachi-Comark','model' => 'PARALLAX', 'authorized_erp_kw' => 600.0, 'measured_power_kw' => 588.0, 'eas' => true, 'endec' => 'Trilithic EASyCAP'],
            ['call' => 'KPOW-AM', 'manufacturer' => 'Broadcast Electronics','model' => 'AM-1A', 'authorized_erp_kw' => 1.0, 'measured_power_kw' => 0.78, 'eas' => true, 'endec' => 'SAGE 3644 (DUE FOR INSPECTION)'],
            ['call' => 'KZ99-FM', 'manufacturer' => 'Continental','model' => '816R-3D', 'authorized_erp_kw' => 25.0, 'measured_power_kw' => 19.4, 'eas' => false, 'endec' => null],
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

        // ---- Per-license rule status (drive the dashboard "by category") ----
        $ruleObjects = FccRule::all()->keyBy('rule_number');
        foreach (FccLicense::all() as $license) {
            foreach ($ruleObjects as $rule) {
                $score = $license->compliance_score;
                $status = 'compliant';
                if ($score < 70) {
                    $status = in_array($rule->severity, ['high', 'critical']) ? 'non_compliant' : 'at_risk';
                } elseif ($score < 90) {
                    $status = $rule->severity === 'critical' ? 'at_risk' : 'compliant';
                }
                FccLicenseRuleStatus::updateOrCreate(
                    ['license_id' => $license->id, 'fcc_rule_id' => $rule->id],
                    [
                        'status' => $status,
                        'last_evaluated_at' => now()->subDays(rand(1, 30))->toDateString(),
                        'evaluation_notes' => $status === 'compliant'
                            ? 'Reviewed; conforms to ' . $rule->rule_number
                            : 'Deviation observed during weekly review of ' . $rule->title,
                    ]
                );
            }
        }

        // ---- Upcoming FCC deadlines (matches dashboard) ----
        $deadlines = [
            ['title' => 'Quarterly EAS Test Filing (ETRS Form One)', 'deadline_type' => 'quarterly_eas_test', 'due_date' => now()->addDays(18)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'Public File Quarterly Upload', 'deadline_type' => 'public_file_upload', 'due_date' => now()->addDays(28)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'License Renewal — KJKL-TV', 'deadline_type' => 'license_renewal', 'due_date' => now()->addDays(42)->toDateString(), 'status' => 'due_soon', 'license_call' => 'KJKL-TV'],
            ['title' => 'Issues/Programs List (Q2)', 'deadline_type' => 'issues_programs_list', 'due_date' => now()->addDays(57)->toDateString(), 'status' => 'upcoming'],
            ['title' => 'Tower Lighting Quarterly Inspection (47 CFR 17.47)', 'deadline_type' => 'tower_lighting', 'due_date' => now()->addDays(11)->toDateString(), 'status' => 'due_soon'],
            ['title' => 'Biennial Ownership Report (Form 323)', 'deadline_type' => 'ownership_report', 'due_date' => now()->addDays(95)->toDateString(), 'status' => 'upcoming'],
        ];
        foreach ($deadlines as $d) {
            $licenseId = null;
            if (! empty($d['license_call'])) {
                $licenseId = FccLicense::where('call_sign', $d['license_call'])->value('id');
                unset($d['license_call']);
            }
            FccDeadline::updateOrCreate(
                ['title' => $d['title']],
                array_merge($d, ['license_id' => $licenseId])
            );
        }

        // ---- Recent compliance activity (matches dashboard "COMPLIANCE ACTIVITY") ----
        $events = [
            ['call' => 'KXYZ-FM', 'event_type' => 'technical_review_passed', 'summary' => 'License KXYZ-FM passed technical compliance review', 'actor' => 'System', 'minutes_ago' => 2],
            ['call' => 'WQRS-TV', 'event_type' => 'power_warning',           'summary' => 'Rule 73.1212 power-limit warning logged for WQRS-TV', 'actor' => 'System', 'minutes_ago' => 15],
            ['call' => 'WABC-AM', 'event_type' => 'public_file_uploaded',    'summary' => 'Public file documents uploaded for WABC-AM',           'actor' => 'Admin User', 'minutes_ago' => 60],
            ['call' => 'KLMN-LP', 'event_type' => 'eas_test_filed',          'summary' => 'EAS test (RWT) filed for KLMN-LP',                     'actor' => 'Admin User', 'minutes_ago' => 120],
            ['call' => null,      'event_type' => 'report_generated',        'summary' => 'Quarterly report generated for FM stations',           'actor' => 'System', 'minutes_ago' => 180],
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
