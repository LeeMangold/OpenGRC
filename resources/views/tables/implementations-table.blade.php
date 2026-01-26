<!-- resources/views/filament/components/implementations-table.blade.php -->
<h1>Associated Implementations</h1>
@if($implementations && count($implementations) > 0)

    <table class="w-full text-sm text-left rtl:text-right text-black dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            <th scope="col" class="border px-4 py-2">Code</th>
            <th scope="col" class="border px-4 py-2">Title</th>
            <th scope="col" class="border px-4 py-2">Details</th>
            <th scope="col" class="border px-4 py-2">Test Procedure</th>
            </tr>
        </thead>
        <tbody>

        @foreach ($implementations as $implementation)
        <tr>
            <td class="border px-4 py-2"> {{ $implementation->code }}</td>
            <td class="border px-4 py-2">{{ $implementation->title }}</td>
            <td class="border px-4 py-2"> {!! $implementation->details !!} </td>
            <td class="border px-4 py-2"> {!! $implementation->test_procedure !!} </td>
            </tr>
        @endforeach

        </tbody>
        </table>
@else
    <p>No Implementations found.</p>
@endif
