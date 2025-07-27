<html>
<head>
    <meta charset="utf-8">
    <title>Audit Item Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1, h2, h3 { color: #333; }
        .section { margin-bottom: 30px; }
        .data-request { margin-bottom: 15px; }
        .response { margin-left: 20px; color: #444; }
    </style>
</head>
<body>
    <center><h1>Audit Evidence</h1></center>
    <center><h2>{{ $audit->title }}</h2></center>
    <center><h3>Request Code: {{ $dataRequest->code ?? $dataRequest->id }}</h3></center>
    <div class="section">
        <p>
            <strong>Title:</strong> {{ $auditItem->auditable->title ?? '' }}<br>
            <strong>Description:</strong> {!! html_entity_decode($auditItem->auditable->description ?? '') !!}
        </p>
    </div>
    <div class="section">
        <h3>Data Request</h3>
        <strong>Requested Information:</strong> {!! html_entity_decode($dataRequest->details) !!}
        <div style="margin-top: 10px;">
            <strong>Responses:</strong>
            @foreach($dataRequest->responses as $response)
                <div class="response">
                    {!! html_entity_decode($response->response) !!}
                    @if($response->attachments && $response->attachments->count())                    
                        @foreach($response->attachments as $attachment)
                            @if($attachment->base64_image)
                                <div style="text-align:center; margin: 20px 0;">
                                    <img src="{{ $attachment->base64_image }}" style="max-width:400px; width:100%; border:2px solid #000; display:block; margin:0 auto;">
                                    <div style="text-align:center; font-size:12px; margin-top:5px;">{{ $attachment->description }}</div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</body>
</html> 