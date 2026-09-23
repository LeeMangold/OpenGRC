<html>
<head>
    <meta charset="utf-8">
    <title>Audit Evidence - {{ $dataRequest->code ?? $dataRequest->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #1375a0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22pt;
            color: #0d5773;
        }
        .header .subtitle {
            font-size: 14pt;
            color: #7eb7d1;
            margin: 0;
        }
        .control-box {
            background-color: #eaf3f7;
            border: 1px solid #a9cfe0;
            border-left: 4px solid #1375a0;
            padding: 15px;
            margin-bottom: 25px;
        }
        .control-box .code {
            font-size: 13pt;
            font-weight: bold;
            color: #1375a0;
            margin-bottom: 5px;
        }
        .control-box .title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .control-box .description {
            font-size: 10pt;
            color: #0a485d;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0d5773;
            border-bottom: 1px solid #a9cfe0;
            padding-bottom: 5px;
            margin-bottom: 12px;
        }
        .request-text {
            background-color: #fefce8;
            border: 1px solid #fef08a;
            padding: 12px;
            font-size: 10pt;
        }
        .narrative {
            padding: 12px;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            margin-bottom: 15px;
        }
        .policy-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .policy-table th {
            background-color: #106689;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .policy-table td {
            border: 1px solid #a9cfe0;
            padding: 10px;
        }
        .policy-table tr:nth-child(even) {
            background-color: #eaf3f7;
        }
        .attachment {
            text-align: center;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .attachment img {
            max-width: 450px;
            border: 1px solid #a9cfe0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .attachment-caption {
            font-size: 9pt;
            color: #0a485d;
            margin-top: 8px;
            font-style: italic;
        }
        @page {
            margin-bottom: 60px;
        }
        .page-footer {
            position: fixed;
            bottom: -40px;
            right: 0;
            font-size: 9pt;
            color: #7eb7d1;
        }
        .page-footer:after {
            content: "Page " counter(page);
        }
    </style>
</head>
<body>
    <div class="page-footer"></div>
    <div class="header">
        <h1>{{ $audit->title }}</h1>
        <p class="subtitle">Evidence Package: {{ $dataRequest->code ?? $dataRequest->id }}</p>
    </div>

    @php
        $controlCode = '';
        $controlTitle = '';
        $controlDescription = '';

        if(isset($auditItems) && count($auditItems) > 0) {
            $firstItem = $auditItems->first();
            $controlCode = $firstItem->auditable->code ?? '';
            $controlTitle = $firstItem->auditable->title ?? '';
            $controlDescription = $firstItem->auditable->description ?? '';
        } elseif(isset($auditItem) && $auditItem->auditable) {
            $controlCode = $auditItem->auditable->code ?? '';
            $controlTitle = $auditItem->auditable->title ?? '';
            $controlDescription = $auditItem->auditable->description ?? '';
        }
    @endphp

    <div class="control-box">
        <div class="code">{{ $controlCode }}</div>
        <div class="title">{{ $controlTitle }}</div>
        <div class="description">@safeHtml(html_entity_decode($controlDescription ?? ''))</div>
    </div>

    <div class="section">
        <div class="section-title">Evidence Request</div>
        <div class="request-text">
            @safeHtml(html_entity_decode($dataRequest->details ?? ''))
        </div>
    </div>

    <div class="section">
        <div class="section-title">Evidence Response</div>

        @php
            $allPolicies = collect();
            foreach($dataRequest->responses as $response) {
                if($response->policyAttachments && $response->policyAttachments->count()) {
                    $allPolicies = $allPolicies->merge($response->policyAttachments);
                }
            }
        @endphp

        @foreach($dataRequest->responses as $response)
            @if($response->response)
                <div class="narrative">
                    @safeHtml(html_entity_decode($response->response ?? ''))
                </div>
            @endif
        @endforeach

        @if($allPolicies->count() > 0)
            <table class="policy-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Supporting Policy</th>
                        <th style="width: 65%;">Relevance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allPolicies as $policyAttachment)
                        <tr>
                            <td><strong>{{ $policyAttachment->policy->code ?? '' }}</strong> - {{ $policyAttachment->policy->name ?? 'Unknown' }}</td>
                            <td>{{ $policyAttachment->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @foreach($dataRequest->responses as $response)
            @if($response->attachments && $response->attachments->count())
                @foreach($response->attachments as $attachment)
                    @if($attachment->base64_image)
                        <div class="attachment">
                            <img src="{{ $attachment->base64_image }}">
                            @if($attachment->description)
                                <div class="attachment-caption">{{ $attachment->description }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>
</body>
</html>
