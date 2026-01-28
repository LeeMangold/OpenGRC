<x-filament-panels::page>
    @php
        $record = $this->getRecord();
    @endphp

    <div class="max-w-4xl mx-auto">

        {{-- Document-style Policy View --}}
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 md:p-12 prose dark:prose-invert max-w-none">
            {{-- Policy Header Table --}}
            <div class="not-prose mb-8">
                <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100">{{ $record->name }}</h1>   
                <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <tbody>
                        {{-- Row 1: Policy ID, Effective Date, Owner --}}
                        <tr>
                            <td class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 w-32">
                                Policy ID
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                {{ $record->code }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 w-40">
                                Effective Date
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                {{ $record->effective_date ? $record->effective_date->format('n/j/Y') : 'Not set' }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 w-32">
                                Owner
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                {{ $record->owner?->name ?? 'Not assigned' }}
                            </td>
                        </tr>

                        {{-- Row 2: Purpose --}}
                        <tr>
                            <td class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 align-top">
                                Purpose
                            </td>
                            <td colspan="5" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $record->purpose ?: 'No purpose defined' !!}
                                </div>
                            </td>
                        </tr>

                        {{-- Row 3: Scope --}}
                        <tr>
                            <td class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 align-top">
                                Scope
                            </td>
                            <td colspan="5" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                <div class="prose dark:prose-invert max-w-none">
                                    {!! $record->policy_scope ?: 'No scope defined' !!}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Retired Date (if applicable) --}}
                <div class="mt-4 flex items-center justify-between">
                    @if($record->retired_date && in_array($record->status?->name, ['Retired', 'Superseded', 'Archived']))
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>Retired Date:</strong> {{ $record->retired_date->format('n/j/Y') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Policy Body Section --}}
            @if($record->body)
                <div class="mb-8 policy-body">
                    <div class="text-gray-800 dark:text-gray-200
                        [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:mt-8 [&_h1]:mb-4 [&_h1]:text-gray-900 dark:[&_h1]:text-gray-100
                        [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:mt-6 [&_h2]:mb-3 [&_h2]:text-gray-900 dark:[&_h2]:text-gray-100
                        [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:text-gray-900 dark:[&_h3]:text-gray-100
                        [&_h4]:text-lg [&_h4]:font-medium [&_h4]:mt-4 [&_h4]:mb-2 [&_h4]:text-gray-900 dark:[&_h4]:text-gray-100
                        [&_p]:mb-4 [&_ul]:mb-4 [&_ol]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6">
                        {!! $record->body !!}
                    </div>
                </div>
            @endif

            {{-- Document Upload Notice --}}
            @if($record->document_path)
                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-check" class="w-5 h-5" />
                        <span>A policy document has been uploaded for this policy.</span>
                    </p>
                </div>
            @endif

            {{-- Revision History Table --}}
            @if($record->revision_history && count($record->revision_history) > 0)
                <div class="mt-12 not-prose">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Revision History</h2>
                    <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                    Version
                                </th>
                                <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                    Date
                                </th>
                                <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                    Author
                                </th>
                                <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                    Changes
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->revision_history as $revision)
                                <tr>
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                        {{ $revision['version'] ?? '' }}
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                        {{ isset($revision['date']) ? \Carbon\Carbon::parse($revision['date'])->format('n/j/Y') : '' }}
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                        {{ $revision['author'] ?? '' }}
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                        {!! $revision['changes'] ?? '' !!}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
