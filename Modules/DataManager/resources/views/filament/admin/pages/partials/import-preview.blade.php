<div class="space-y-4">
    @if(empty($this->preview_data))
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>No preview data available</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Row
                        </th>
                        @foreach($this->column_mapping as $csvIndex => $dbField)
                            @if($dbField)
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $this->db_fields[$dbField]['label'] ?? $dbField }}
                                </th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach(array_slice($this->preview_data, 0, 5) as $rowIndex => $row)
                        <tr class="{{ $rowIndex % 2 === 0 ? '' : 'bg-gray-50 dark:bg-gray-800/50' }}">
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $rowIndex + 1 }}
                            </td>
                            @php
                                $csvHeaders = array_keys($row);
                            @endphp
                            @foreach($this->column_mapping as $csvIndex => $dbField)
                                @if($dbField)
                                    @php
                                        $csvHeader = $csvHeaders[$csvIndex] ?? null;
                                        $value = $csvHeader ? ($row[$csvHeader] ?? '') : '';
                                    @endphp
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $value }}">
                                        {{ \Illuminate\Support\Str::limit($value, 50) }}
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($this->preview_data) > 5)
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                Showing first 5 of {{ $this->total_rows }} rows
            </p>
        @endif
    @endif
</div>
