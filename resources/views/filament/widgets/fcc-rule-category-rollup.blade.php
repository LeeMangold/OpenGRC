<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Compliance by Rule Category</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-amber-400/20 text-amber-400 uppercase text-xs tracking-wide">
                        <th class="text-left py-2 px-2">Category</th>
                        <th class="text-right py-2 px-2">Compliant</th>
                        <th class="text-right py-2 px-2">At Risk</th>
                        <th class="text-right py-2 px-2">Non-Compliant</th>
                        <th class="text-right py-2 px-2">Compliance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getCategoryRows() as $row)
                        <tr class="@if(!empty($row['is_total'])) border-t border-amber-400/30 font-semibold text-amber-300 @else border-b border-white/5 @endif">
                            <td class="py-2 px-2">{{ $row['category'] }}</td>
                            <td class="py-2 px-2 text-right text-emerald-400">{{ number_format($row['compliant']) }}</td>
                            <td class="py-2 px-2 text-right text-amber-400">{{ number_format($row['at_risk']) }}</td>
                            <td class="py-2 px-2 text-right text-red-400">{{ number_format($row['non_compliant']) }}</td>
                            <td class="py-2 px-2 text-right">{{ $row['percent'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
