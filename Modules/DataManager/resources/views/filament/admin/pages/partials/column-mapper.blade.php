<div class="space-y-4">
    @if(empty($this->csv_headers))
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2">Please upload a CSV file first</p>
        </div>
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Map columns from your CSV file to database fields. Required fields are marked with <span class="text-danger-500 font-bold">*</span>
        </div>

        {{-- Header row --}}
        <div class="grid grid-cols-2 gap-4 font-semibold text-sm border-b border-gray-200 dark:border-gray-700 pb-2 mb-2">
            <div class="text-gray-700 dark:text-gray-300">CSV Column</div>
            <div class="text-gray-700 dark:text-gray-300">Database Field</div>
        </div>

        {{-- Mapping rows --}}
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach($this->csv_headers as $index => $header)
                <div class="grid grid-cols-2 gap-4 items-center py-2 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                            {{ $header }}
                        </span>
                        @if(isset($this->column_mapping[$index]))
                            <svg class="w-4 h-4 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <select
                            wire:change="updateMapping({{ $index }}, $event.target.value)"
                            class="fi-select-input block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 dark:text-white"
                        >
                            <option value="">-- Skip this column --</option>
                            @foreach($this->db_fields as $fieldName => $fieldConfig)
                                <option
                                    value="{{ $fieldName }}"
                                    @selected(($this->column_mapping[$index] ?? null) === $fieldName)
                                >
                                    {{ $fieldConfig['label'] }}
                                    @if(isset($this->required_fields[$fieldName]))
                                        *
                                    @endif
                                    @if($fieldConfig['field_type'] === 'enum')
                                        (enum)
                                    @elseif($fieldConfig['is_foreign_key'] ?? false)
                                        (FK)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Required fields reminder --}}
        @if(!empty($this->required_fields))
            <div class="mt-4 p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                <p class="text-sm font-medium text-warning-800 dark:text-warning-200 mb-2">
                    Required fields:
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($this->required_fields as $fieldName => $fieldConfig)
                        @php
                            $isMapped = in_array($fieldName, $this->column_mapping);
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $isMapped ? 'bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100' : 'bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100' }}">
                            {{ $fieldConfig['label'] }}
                            @if($isMapped)
                                <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
