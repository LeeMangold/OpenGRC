@extends('reports.layout')
@section('content')

    <div id="header">
        <table width="100%" style="border: 0; border-collapse: collapse;">
            <tr>
                <td width="50%" style="text-align: left; border: 0; padding: 0;">
                    <b>AUDIT REPORT</b>
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
        <center><h1>Audit Report</h1></center>

        <br><br>
            @php
                $logoPath = setting('report.logo')
                    ? storage_path('app/public/' . setting('report.logo'))
                    : public_path('img/logo.png');
            @endphp
        <center>
            <img style="max-width: 350px" src="{{ $logoPath }}" alt="Report Logo">
        </center>
        <br><br>
        <center><h2>{{ $audit->title }}</h2></center>
        <center>Audit Date: {{ $audit->start_date }} - {{ $audit->end_date }}</center>
        </div>




        <div class="page-break"></div>

        <center><h2>Implementations Summary</h2></center>
        <p>The following table depicts all the control implementations tested in this audit. Each completed
        implementation in the table was assessed for both implementation status  and the effectiveness
        the implementation. Evidence was collected during the period of performance and includes
        interviews, observations, and document reviews.
        </p>

        <table class="table table-striped" width="100%" border="1">
            <thead>
            <tr>
                <th>Implementations</th>
                <th>Status</th>
                <th>Effectiveness</th>
            </tr>
            </thead>
            <tbody>
            @foreach($auditItems as $item)
                <tr>
                    <td>{{$item->auditable->code}}</td>
                    <td class="{{ strtolower(str_replace(' ', '', $item->status->value)) }}">{{$item->status->value }}</td>
                    <td class="{{ strtolower(str_replace(' ', '', $item->effectiveness->value)) }}">{{ $item->effectiveness->value }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <br><br>
            <div class="page-break"></div>
        <center><h2>Audit Controls Details</h2></center>

        @foreach($auditItems as $item)

            <table border="1" width="100%">
                <tr>
                    <td style="width:25%;">{{ $item->auditable->code }}</td>
                    <td style="width:25%;"
                        class="{{ strtolower(str_replace(' ', '', $item->status->value)) }}">{{$item->status->value}}</td>
                    <td style="width:25%;"
                        class="{{ strtolower(str_replace(' ', '', $item->effectiveness->value)) }}">{{$item->effectiveness->value}}</td>
                </tr>
                <tr>
                    <td>Implementation Title</td>
                    <td colspan="2">{{ $item->auditable->code }} {{ $item->auditable->title }}
                        <br>@safeHtml($item->auditable->details)</td>
                </tr>
                <tr>
                    <td>Auditor Notes</td>
                    <td colspan="2">@safeHtml($item->auditor_notes)</td>
                </tr>
            </table>
            <br><br>
        @endforeach

    </div>
@endsection
