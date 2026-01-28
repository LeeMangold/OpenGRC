@extends('reports.layout')
@php
    use App\Http\Controllers\PdfHelper;
@endphp
@section('content')

    <div id="header">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    <b>{{ $policy->name }}</b>
                </td>
                <td width="50%" style="text-align: right; border: 0; padding: 0;">
                    <b>{{ $policy->code }}</b>
                </td>
            </tr>
        </table>
    </div>

    <div id="footer">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    Downloaded on {{ date('Y-m-d') }}
                </td>
                <td width="50%" style="text-align: right; border: 0; padding: 0;">
                    <p class="page" style="margin-right: 5px"><?php $PAGE_NUM ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div id="content">
        {{-- Policy Title --}}
        <h1 style="text-align: center; margin-bottom: 20px;">{{ $policy->name }}</h1>

        {{-- Policy Header Table (matching HTML view structure) --}}
        <table width="100%" border="1">
            <tbody>
                {{-- Row 1: Policy ID, Effective Date, Owner --}}
                <tr>
                    <td style="width: 15%; background-color: #f2f2f2;"><strong>Policy ID</strong></td>
                    <td style="width: 18%;">{{ $policy->code }}</td>
                    <td style="width: 15%; background-color: #f2f2f2;"><strong>Effective Date</strong></td>
                    <td style="width: 18%;">{{ $policy->effective_date ? $policy->effective_date->format('n/j/Y') : 'Not set' }}</td>
                    <td style="width: 15%; background-color: #f2f2f2;"><strong>Owner</strong></td>
                    <td style="width: 19%;">{{ $policy->owner?->name ?? 'Not assigned' }}</td>
                </tr>

                {{-- Row 2: Purpose --}}
                <tr>
                    <td style="background-color: #f2f2f2; vertical-align: top;"><strong>Purpose</strong></td>
                    <td colspan="5">
                        {!! $policy->purpose ? PdfHelper::convertImagesToBase64($policy->purpose) : 'No purpose defined' !!}
                    </td>
                </tr>

                {{-- Row 3: Scope --}}
                <tr>
                    <td style="background-color: #f2f2f2; vertical-align: top;"><strong>Scope</strong></td>
                    <td colspan="5">
                        {!! $policy->policy_scope ? PdfHelper::convertImagesToBase64($policy->policy_scope) : 'No scope defined' !!}
                    </td>
                </tr>
            </tbody>
        </table>

        @if($policy->retired_date && in_array($policy->status?->name, ['Retired', 'Superseded', 'Archived']))
            <p style="margin-top: 10px;"><strong>Retired Date:</strong> {{ $policy->retired_date->format('n/j/Y') }}</p>
        @endif

        {{-- Policy Body --}}
        @if($policy->body)
            <div style="margin: 30px 0;">
                {!! PdfHelper::convertImagesToBase64($policy->body) !!}
            </div>
        @endif

        {{-- Revision History --}}
        @if($policy->revision_history && count($policy->revision_history) > 0)
            <div class="page-break"></div>
            <h2 style="text-align: center;">Revision History</h2>
            <table width="100%" border="1">
                <thead>
                    <tr>
                        <th style="width: 15%;">Version</th>
                        <th style="width: 20%;">Date</th>
                        <th style="width: 25%;">Author</th>
                        <th style="width: 40%;">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policy->revision_history as $revision)
                        <tr>
                            <td>{{ $revision['version'] ?? '' }}</td>
                            <td>{{ isset($revision['date']) ? \Carbon\Carbon::parse($revision['date'])->format('n/j/Y') : '' }}</td>
                            <td>{{ $revision['author'] ?? '' }}</td>
                            <td>{!! $revision['changes'] ?? '' !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
@endsection
