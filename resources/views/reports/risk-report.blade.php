@extends('reports.layout')
@php
    use Illuminate\Support\Facades\Storage;
    use App\Http\Controllers\PdfHelper;
    use App\Enums\RiskLevel;
    use App\Filament\Resources\RiskResource\Widgets\InherentRisk;
@endphp

@section('content')

    <style>
        /* Landscape mode styles */
        @page {
            size: landscape;
            margin: 100px 50px 80px 50px;
        }

        /* Risk color styles */
        .risk-very-high {
            background-color: #dc3545;
            color: white;
        }
        .risk-high {
            background-color: #fd7e14;
            color: white;
        }
        .risk-moderate {
            background-color: #ffc107;
            color: #000;
        }
        .risk-low {
            background-color: #17a2b8;
            color: white;
        }
        .risk-very-low {
            background-color: #28a745;
            color: white;
        }

        /* Heatmap styles */
        .heatmap-container {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            margin: 0 2%;
        }
        .heatmap-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .heatmap-wrapper {
            display: table;
            width: 100%;
        }
        .heatmap-impact-label {
            display: table-cell;
            width: 20px;
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .heatmap-impact-label span {
            display: inline-block;
            transform: rotate(-90deg);
            white-space: nowrap;
        }
        .heatmap-grid-wrapper {
            display: table-cell;
            vertical-align: middle;
        }
        .heatmap-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .heatmap-grid td {
            width: 16.66%;
            height: 30px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #fff;
            font-weight: bold;
        }
        .heatmap-label {
            font-size: 12px;
            font-weight: normal;
            background: #f2f2f2 !important;
            color: #000 !important;
        }
        .axis-label {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
    </style>

    <div id="header">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    <b>RISK REPORT</b>
                </td>
                <td width="50%" style="text-align: right; border: 0; padding: 0;">
                    <b>CONFIDENTIAL</b>
                </td>
            </tr>
        </table>
    </div>

    <div id="footer">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    Created on {{ date('Y-m-d') }}
                </td>
                <td width="50%" style="text-align: right; border: 0; padding: 0;">
                    <p class="page" style="margin-right: 5px"><?php $PAGE_NUM ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div id="content">
        <div style="margin-top: 50px">
            <center><h1>Risk Report</h1></center>
            <br>

            @php
                $defaultLogoPath = public_path('img/logo.png');
                $customLogo = setting('report.logo');
                $tempPath = null;

                if ($customLogo && Storage::disk(config('filesystems.default'))->exists($customLogo)) {
                    $storage = Storage::disk(config('filesystems.default'));

                    // Create a temporary directory if it doesn't exist
                    $tempDir = storage_path('app/temp');
                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }

                    // Generate a unique temporary file name
                    $tempPath = $tempDir . '/temp_logo_' . uniqid() . '_' . basename($customLogo);

                    // Download and store the file temporarily
                    $fileContents = $storage->get($customLogo);
                    file_put_contents($tempPath, $fileContents);

                    $logoPath = $tempPath;
                } else {
                    $logoPath = $defaultLogoPath;
                }

                // Register a shutdown function to clean up the temporary file
                if ($tempPath) {
                    register_shutdown_function(function() use ($tempPath) {
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                    });
                }
            @endphp

            <center>
                <img style="max-width: 350px" src="{{ $logoPath }}" alt="Report Logo">
            </center>
            <br>
            <center>Generated: {{ date('F d, Y') }}</center>
        </div>

        <div class="page-break"></div>

        <center><h2>Risk Heatmaps</h2></center>
        <br><br>

        @php
            $inherentGrid = array_reverse(InherentRisk::generateGrid($risks, 'inherent'));
            $residualGrid = array_reverse(InherentRisk::generateGrid($risks, 'residual'));
        @endphp

        <div style="text-align: center;">
            <!-- Inherent Risk Heatmap -->
            <div class="heatmap-container">
                <div class="heatmap-title">Inherent Risk</div>
                <div class="heatmap-wrapper">
                    <div class="heatmap-impact-label">
                        <span>Impact</span>
                    </div>
                    <div class="heatmap-grid-wrapper">
                        <table class="heatmap-grid">
                            <!-- Column headers -->
                            <tr>
                                <td class="heatmap-label"></td>
                                <td class="heatmap-label">Very Low</td>
                                <td class="heatmap-label">Low</td>
                                <td class="heatmap-label">Moderate</td>
                                <td class="heatmap-label">High</td>
                                <td class="heatmap-label">Very High</td>
                            </tr>
                            @foreach ($inherentGrid as $impactIndex => $impactRow)
                                <tr>
                                    @php
                                        $impactLabels = ['Very Low', 'Low', 'Moderate', 'High', 'Very High'];
                                        // Reverse the impact index: 0 becomes 4 (Very High at top), 4 becomes 0 (Very Low at bottom)
                                        $displayImpactIndex = 4 - $impactIndex;
                                        $actualImpact = $displayImpactIndex + 1; // Convert to 1-5 scale
                                    @endphp
                                    <td class="heatmap-label">{{ $impactLabels[$displayImpactIndex] }}</td>
                                    @foreach ($impactRow as $likelihoodIndex => $cellRisks)
                                        @php
                                            $count = count($cellRisks);
                                            $actualLikelihood = $likelihoodIndex + 1; // Convert to 1-5 scale
                                            // Use weight 200 for empty cells, 500 for cells with risks
                                            $colorWeight = $count > 0 ? 500 : 200;
                                            $colorClass = RiskLevel::getColor($actualLikelihood, $actualImpact, $colorWeight);
                                            $colorClass = str_replace('bg-', '', $colorClass);
                                            $bgColor = match($colorClass) {
                                                'red-500' => '#dc3545',
                                                'orange-500' => '#fd7e14',
                                                'yellow-500' => '#ffc107',
                                                'grcblue-500' => '#17a2b8',
                                                'green-500' => '#28a745',
                                                'red-200' => '#f5c2c7',
                                                'orange-200' => '#ffe5d0',
                                                'yellow-200' => '#fff3cd',
                                                'grcblue-200' => '#bee5eb',
                                                'green-200' => '#d1e7dd',
                                                default => '#f2f2f2',
                                            };
                                            $textColor = in_array($colorClass, ['yellow-500', 'yellow-200']) ? '#000' : '#fff';
                                        @endphp
                                        <td style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                            {{ $count > 0 ? $count : '' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <!-- Likelihood label row -->
                            <tr>
                                <td colspan="6" class="axis-label">Likelihood</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Residual Risk Heatmap -->
            <div class="heatmap-container">
                <div class="heatmap-title">Residual Risk</div>
                <div class="heatmap-wrapper">
                    <div class="heatmap-impact-label">
                        <span>Impact</span>
                    </div>
                    <div class="heatmap-grid-wrapper">
                        <table class="heatmap-grid">
                            <!-- Column headers -->
                            <tr>
                                <td class="heatmap-label"></td>
                                <td class="heatmap-label">Very Low</td>
                                <td class="heatmap-label">Low</td>
                                <td class="heatmap-label">Moderate</td>
                                <td class="heatmap-label">High</td>
                                <td class="heatmap-label">Very High</td>
                            </tr>
                            @foreach ($residualGrid as $impactIndex => $impactRow)
                                <tr>
                                    @php
                                        $impactLabels = ['Very Low', 'Low', 'Moderate', 'High', 'Very High'];
                                        // Reverse the impact index: 0 becomes 4 (Very High at top), 4 becomes 0 (Very Low at bottom)
                                        $displayImpactIndex = 4 - $impactIndex;
                                        $actualImpact = $displayImpactIndex + 1; // Convert to 1-5 scale
                                    @endphp
                                    <td class="heatmap-label">{{ $impactLabels[$displayImpactIndex] }}</td>
                                    @foreach ($impactRow as $likelihoodIndex => $cellRisks)
                                        @php
                                            $count = count($cellRisks);
                                            $actualLikelihood = $likelihoodIndex + 1; // Convert to 1-5 scale
                                            // Use weight 200 for empty cells, 500 for cells with risks
                                            $colorWeight = $count > 0 ? 500 : 200;
                                            $colorClass = RiskLevel::getColor($actualLikelihood, $actualImpact, $colorWeight);
                                            $colorClass = str_replace('bg-', '', $colorClass);
                                            $bgColor = match($colorClass) {
                                                'red-500' => '#dc3545',
                                                'orange-500' => '#fd7e14',
                                                'yellow-500' => '#ffc107',
                                                'grcblue-500' => '#17a2b8',
                                                'green-500' => '#28a745',
                                                'red-200' => '#f5c2c7',
                                                'orange-200' => '#ffe5d0',
                                                'yellow-200' => '#fff3cd',
                                                'grcblue-200' => '#bee5eb',
                                                'green-200' => '#d1e7dd',
                                                default => '#f2f2f2',
                                            };
                                            $textColor = in_array($colorClass, ['yellow-500', 'yellow-200']) ? '#000' : '#fff';
                                        @endphp
                                        <td style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                            {{ $count > 0 ? $count : '' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <!-- Likelihood label row -->
                            <tr>
                                <td colspan="6" class="axis-label">Likelihood</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <br>
        <table width="100%" border="1">
            <thead>
                <tr>
                    <th style="width: 15%;">Name</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 10%;">Inherent Risk</th>
                    <th style="width: 25%;">Implementations</th>
                    <th style="width: 10%;">Residual Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($risks as $risk)
                    @php
                        // Get heatmap colors for the cells
                        $inherentColorClass = RiskLevel::getColor($risk->inherent_likelihood, $risk->inherent_impact, 500);
                        $inherentColorClass = str_replace('bg-', '', $inherentColorClass);
                        $inherentBgColor = match($inherentColorClass) {
                            'red-500' => '#dc3545',
                            'orange-500' => '#fd7e14',
                            'yellow-500' => '#ffc107',
                            'grcblue-500' => '#17a2b8',
                            'green-500' => '#28a745',
                            default => '#f2f2f2',
                        };
                        $inherentTextColor = in_array($inherentColorClass, ['yellow-500']) ? '#000' : '#fff';

                        $residualColorClass = RiskLevel::getColor($risk->residual_likelihood, $risk->residual_impact, 500);
                        $residualColorClass = str_replace('bg-', '', $residualColorClass);
                        $residualBgColor = match($residualColorClass) {
                            'red-500' => '#dc3545',
                            'orange-500' => '#fd7e14',
                            'yellow-500' => '#ffc107',
                            'grcblue-500' => '#17a2b8',
                            'green-500' => '#28a745',
                            default => '#f2f2f2',
                        };
                        $residualTextColor = in_array($residualColorClass, ['yellow-500']) ? '#000' : '#fff';

                        // Calculate risk scores for labels
                        $inherentScore = round(($risk->inherent_likelihood + $risk->inherent_impact) / 2);
                        $residualScore = round(($risk->residual_likelihood + $risk->residual_impact) / 2);

                        // Map scores to labels
                        $riskLabels = [
                            5 => 'Very High',
                            4 => 'High',
                            3 => 'Moderate',
                            2 => 'Low',
                            1 => 'Very Low',
                        ];

                        $inherentLabel = $riskLabels[$inherentScore] ?? 'Unknown';
                        $residualLabel = $riskLabels[$residualScore] ?? 'Unknown';
                    @endphp
                    <tr>
                        <td>{{ $risk->name }}</td>
                        <td>{{ $risk->description ?? 'N/A' }}</td>
                        <td style="background-color: {{ $inherentBgColor }}; color: {{ $inherentTextColor }}; text-align: center; font-weight: bold;">
                            {{ $inherentLabel }}
                        </td>
                        <td>
                            @if($risk->implementations->count() > 0)
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach($risk->implementations as $implementation)
                                        <li>{{ $implementation->title }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <em>No implementations</em>
                            @endif
                        </td>
                        <td style="background-color: {{ $residualBgColor }}; color: {{ $residualTextColor }}; text-align: center; font-weight: bold;">
                            {{ $residualLabel }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($risks->count() === 0)
            <p><em>No risks have been identified yet.</em></p>
        @endif

    </div>
@endsection
