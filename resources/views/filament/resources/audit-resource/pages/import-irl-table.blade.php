@php

    use League\Csv\Reader;



@endphp

@if (is_array($data) && count($data) > 0)

    <table class='fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5'>

        <thead>
        <tr>
            <th class='fi-ta-th'>{{ __('Action') }}</th>
            <th class='fi-ta-th'>{{ __('Audit ID') }}</th>
            <th class='fi-ta-th'>{{ __('Request ID') }}</th>
            <th class='fi-ta-th'>{{ __('Control Code') }}</th>
            <th class='fi-ta-th'>{{ __('Details') }}</th>
            <th class='fi-ta-th'>{{ __('Assigned To') }}</th>
            <th class='fi-ta-th'>{{ __('Due On') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach($data as $record)
            <tr class='fi-ta-tr'>
                @php

                    if ($record["_ACTION"] == "UPDATE") {
                        $action= "update";
                        $action_html = '<span style="--c-50:var(--warning-50);--c-400:var(--warning-400);--c-600:var(--warning-600);"
                                        class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-warning">
                                        UPDATE
                                        </span>';
                    } else if($record["_ACTION"] == "CREATE") {
                        $action= "add";
                        $action_html = '<span style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);"
                                        class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-success">
                                        CREATE
                                        </span>';
                    }
                @endphp

                <td class='fi-ta-td text-center align-middle'>{!! $action_html !!}</td>
                <td class='fi-ta-td text-center align-middle'>{{ $record["Audit ID"] }}</td>
                <td class='fi-ta-td text-center align-middle'>{{ $record["Request ID"] ?? 'New'}}</td>
                <td class='fi-ta-td text-center align-middle'>{{ $record["Control Code"] }}</td>
                <td class='fi-ta-td'>{{ $record["Details"] }}</td>
                <td class='fi-ta-td text-center align-middle'>{!! $record["Assigned To"] !!}</td>
                <td class='fi-ta-td text-center align-middle'>{{ $record["Due On"] }}</td>
            </tr>
        @endforeach
        </tbody>

    </table>

@else

    <div class='fi-ta-empty'>
        <div class='fi-ta-empty-icon'>
            <x-heroicon-o-document-text class='h-8 w-8 text-gray-400'/>
        </div>
        <div class='fi-ta-empty-text'>
            No IRL has been loaded yet.
        </div>
    </div>

@endif