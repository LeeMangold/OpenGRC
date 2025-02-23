<!-- resources/views/filament/components/data-requests-table.blade.php -->
@php
    use Carbon\Carbon;
@endphp
<h1>Associated Data Requests</h1>
@isset($requests)

    <table class="w-full text-sm text-left rtl:text-right text-black dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th scope="col" class="border px-4 py-2">Request made</th>
            <th scope="col" class="border px-4 py-2">Response given</th>
            <th scope="col" class="border px-4 py-2">Due Date</th>
            <th scope="col" class="border px-4 py-2">Status</th>
        </tr>
        </thead>
        <tbody>

        @foreach ($requests as $request)
            <tr>
                <td class="border px-4 py-2">
                    <a class="underline"
                       href="{!! route('filament.app.resources.data-requests.edit', $request->id) !!}">
                        {{ $request->details }}
                    </a>
                </td>

                <td class="border px-4 py-2">

                    @isset($request->responses)
                        <ul class="list-disc p-4">
                            @foreach ($request->responses as $response)
                                <li>
                                    {!! $response->response !!}
                                    @if($response->attachments->count() > 0)
                                        (has attachments)
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endisset

                </td>

                <td class="border px-4 py-2">{{ Carbon::parse($request->responses->first()->due_at)->format('F j, Y') }}</td>
                <td class="border px-4 py-2"> {!! $request->responses->first()->status->value !!} </td>
            </tr>
        @endforeach

        </tbody>
    </table>
@else
    <p>No Implementations found.</p>
@endif
