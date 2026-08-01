<?php

namespace App\Http\Controllers;

use App\Models\FccLicense;
use App\Models\FccLicenseRuleStatus;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FccComplianceReportController extends Controller
{
    /**
     * Stream a CSV summary suitable for sharing with FCC compliance leadership.
     * Columns mirror the dashboard: per-license + per-rule-category rollup.
     */
    public function csv(): StreamedResponse
    {
        $filename = 'fcc-compliance-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // Header section
            fputcsv($out, ['OpenGRC FCC Compliance — Report']);
            fputcsv($out, ['Generated', now()->toIso8601String()]);
            fputcsv($out, []);

            // License summary
            fputcsv($out, ['License Compliance']);
            fputcsv($out, [
                'Call Sign', 'FRN', 'Licensee', 'Service', 'Channel/Frequency',
                'Status', 'Expiration', 'Compliance Score (%)',
            ]);
            foreach (FccLicense::query()->orderBy('call_sign')->get() as $license) {
                fputcsv($out, [
                    $license->call_sign,
                    $license->frn,
                    $license->licensee,
                    $license->service,
                    $license->channel_or_frequency,
                    str($license->status)->headline(),
                    optional($license->expiration_date)->format('Y-m-d'),
                    $license->compliance_score,
                ]);
            }
            fputcsv($out, []);

            // Rule status rollup per license
            fputcsv($out, ['Rule Status by License']);
            fputcsv($out, ['Call Sign', 'CFR Section', 'Rule Title', 'Category', 'Severity', 'Status', 'Last Evaluated', 'Notes']);
            FccLicenseRuleStatus::query()
                ->with(['license', 'rule'])
                ->orderBy('license_id')
                ->orderByDesc('status')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        if (! $row->license || ! $row->rule) continue;
                        fputcsv($out, [
                            $row->license->call_sign,
                            $row->rule->rule_number,
                            $row->rule->title,
                            str($row->rule->category)->headline(),
                            ucfirst($row->rule->severity),
                            str($row->status)->headline(),
                            optional($row->last_evaluated_at)->format('Y-m-d'),
                            $row->evaluation_notes,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
