<!-- resources/views/filament/components/data-requests-table.blade.php -->
<h1>Associated Data Requests</h1>
@isset($requests)

    <table class="w-full text-sm text-left rtl:text-right text-black dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th scope="col" class="border px-4 py-2">Request made</th>
            <th scope="col" class="border px-4 py-2">Response given</th>
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
                        <ol class="list-decimal">
                            @foreach ($request->responses as $response)
                                <li>
                                    {!! $response->response !!}
                                    @if($response->attachments->count() > 0)
                                        (has attachments)
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endisset

                </td>
                <td class="border px-4 py-2"> {!! $request->status !!} </td>
            </tr>
        @endforeach

        </tbody>
    </table>
@else
    <p>No Implementations found.</p>
@endif
