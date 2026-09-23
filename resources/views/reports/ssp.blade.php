@extends('reports.layout')
@php
    use Illuminate\Support\Facades\Storage;
    use App\Http\Controllers\PdfHelper;
@endphp
@section('content')

    <div id="header">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    <b>SYSTEM SECURITY PLAN</b>
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
        <div style="margin-top: 100px">
            <center><h1>System Security Plan</h1></center>
            <br><br>

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
            <br><br>
            <center><h2>{{ $program->name }}</h2></center>
            @if($program->programManager)
                <center>Program Manager: {{ $program->programManager->name }}</center>
            @endif
            <center>Generated: {{ date('F d, Y') }}</center>
        </div>

        <div class="page-break"></div>

        @if($program->description)
            <center><h2>Program Description</h2></center>
            <div style="margin: 20px 0;">
                @safePdfHtml($program->description)
            </div>
            <div class="page-break"></div>
        @endif

        <center><h2>Program Details</h2></center>
        <table width="100%" border="1">
            <tr>
                <td style="width: 30%; background-color: #f2f2f2;"><strong>Program Name</strong></td>
                <td style="width: 70%;">{{ $program->name }}</td>
            </tr>
            @if($program->programManager)
            <tr>
                <td style="background-color: #f2f2f2;"><strong>Program Manager</strong></td>
                <td>{{ $program->programManager->name }}</td>
            </tr>
            @endif
            <tr>
                <td style="background-color: #f2f2f2;"><strong>Scope Status</strong></td>
                <td>{{ $program->scope_status }}</td>
            </tr>
            @if($program->last_audit_date)
            <tr>
                <td style="background-color: #f2f2f2;"><strong>Last Audit Date</strong></td>
                <td>{{ $program->last_audit_date->format('F d, Y') }}</td>
            </tr>
            @endif
        </table>

        <br><br>

        <center><h2>Applicable Standards</h2></center>
        @if($program->standards->count() > 0)
            <table width="100%" border="1">
                <thead>
                    <tr>
                        <th>Standard Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($program->standards as $standard)
                        <tr>
                            <td>{{ $standard->name }}</td>
                            <td>{{ $standard->description ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No standards have been assigned to this program.</p>
        @endif

        <div class="page-break"></div>

        <center><h2>Control Implementation Summary</h2></center>
        <p>The following table lists all controls applicable to this program and their implementation status.</p>

        <table class="table table-striped" width="100%" border="1">
            <thead>
                <tr>
                    <th style="width: 15%;">Control Code</th>
                    <th style="width: 45%;">Control Title</th>
                    <th style="width: 20%;">Standard</th>
                    <th style="width: 20%;">Implementations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($controls as $control)
                    <tr>
                        <td>{{ $control->code }}</td>
                        <td>{{ $control->title }}</td>
                        <td>{{ $control->standard->name ?? 'N/A' }}</td>
                        <td>{{ $control->implementations->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="page-break"></div>

        <center><h2>Control Implementation Details</h2></center>

        @foreach($controls as $control)
            <table border="1" width="100%">
                <tr>
                    <td colspan="2" style="background-color: #f2f2f2;">
                        <strong>{{ $control->code }} - {{ $control->title }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%; background-color: #f9f9f9;"><strong>Standard</strong></td>
                    <td style="width: 75%;">{{ $control->standard->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9;"><strong>Control Description</strong></td>
                    <td>@safePdfHtml($control->description)</td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9;"><strong>Applicability</strong></td>
                    <td>{{ $control->applicability?->value ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9;"><strong>Effectiveness</strong></td>
                    <td>{{ $control->effectiveness?->value ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9;"><strong>Implementations</strong></td>
                    <td>
                        @if($control->implementations->count() > 0)
                            @foreach($control->implementations as $implementation)
                                <div style="margin-bottom: 15px;">
                                    <strong>Implementation {{ $loop->iteration }}:</strong>
                                    <br>
                                    @safePdfHtml($implementation->details)
                                    @if($implementation->status)
                                        <br><em>Status: {{ $implementation->status->value }}</em>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <em>No implementations documented.</em>
                        @endif
                    </td>
                </tr>
            </table>
            <br><br>
        @endforeach

    </div>
@endsection
