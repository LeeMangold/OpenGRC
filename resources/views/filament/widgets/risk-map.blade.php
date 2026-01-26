@php
    use App\Filament\Resources\RiskResource\Widgets\InherentRisk;
@endphp

<x-filament-widgets::widget class="overflow-visible">
    <x-filament::card class="overflow-visible">
        <div class="bg-grcblue-200 bg-red-200 bg-red-500 bg-orange-200 bg-orange-500"></div>
        <div class="bg-grcblue-500 bg-green-200 bg-green-500 bg-yellow-200 bg-yellow-500"></div>
        <div class="bg-grcblue-500"></div>
        <header class="fi-section-header flex flex-col gap-3 px-6 py-3.5">
            <div class="flex items-center gap-3">
                <div class="grid flex-1 gap-y-1">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white text-center">
                        {{ $title }}
                    </h3>
                </div>
            </div>
        </header>

        <!-- Top section: Impact + row labels + main 5-col grid -->
        <div class="flex h-[300px] overflow-visible">
            <!-- Narrow column for rotated "Impact" label -->
            <div style="width: 0;" class="flex items-center justify-center flex-none">
                <div class="transform -rotate-90 text-sm font-bold leading-none">
                    Impact
                </div>
            </div>

            <!-- Row labels + main grid -->
            <div class="flex-1 flex items-start overflow-visible">
                <!-- Row labels column -->
                <div class="w-20 flex flex-col gap-0.5">
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Very High</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">High</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Moderate</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Low</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Very Low</div>
                </div>

                <!-- 5-col risk map grid -->
                <div class="flex-1 overflow-visible">
                    <div class="grid grid-cols-5 gap-0.5 h-full w-full overflow-visible">
                        @foreach (array_reverse($grid) as $impactIndex => $impactRow)
                            @foreach ($impactRow as $likelihoodIndex => $risks)
                                @php
                                    $count = count($risks);
                                    $colorWeight = 200;
                                    if($count > 0) {
                                        $colorWeight = 500;
                                    }
                                    $colorClass = \App\Enums\RiskLevel::getColor($likelihoodIndex + 1, sizeof($grid) - $impactIndex, $colorWeight);

                                    // Calculate actual likelihood and impact values (1-5)
                                    $likelihoodValue = $likelihoodIndex + 1;
                                    $impactValue = sizeof($grid) - $impactIndex;

                                    // Build the filter URL based on risk type
                                    $cellFilterUrl = $filterUrl . '?' . http_build_query([
                                        'tableFilters' => [
                                            $type . '_likelihood' => ['value' => $likelihoodValue],
                                            $type . '_impact' => ['value' => $impactValue],
                                        ]
                                    ]);

                                    // Determine tooltip position based on column (0-4)
                                    // Left columns (0,1): show tooltip to the right
                                    // Middle and right columns (2,3,4): show tooltip to the left to avoid edge
                                    $showOnLeft = $likelihoodIndex >= 2;
                                @endphp

                                <div
                                        x-data="{ show: false }"
                                        x-on:click="$dispatch('filter-risks', { type: '{{ $type }}', likelihood: {{ $likelihoodValue }}, impact: {{ $impactValue }} })"
                                        class="text-center flex items-center justify-center {{ $colorClass }} transition-opacity hover:opacity-80 cursor-pointer"
                                        style="height: 60px;"
                                        @mouseenter="show = true"
                                        @mouseleave="show = false"
                                >
                                    @if ($count > 0)
                                        <div class="font-extrabold relative">
                                            {{ $count }}
                                            <div
                                                    x-show="show"
                                                    x-cloak
                                                    class="absolute z-10 bg-gray-800 text-white text-xs rounded py-2 px-3 top-0 shadow-lg whitespace-nowrap overflow-y-auto max-h-48 text-left {{ $showOnLeft ? 'right-full mr-2' : 'left-full ml-2' }}"
                                                    style="min-width: 200px; max-width: 300px;"
                                            >
                                                <div class="font-medium mb-1">Risks:</div>
                                                <ul class="list-disc list-outside pl-4 space-y-0.5">
                                                @foreach($risks as $risk)
                                                    <li class="ml-0 truncate" title="{{ $risk->name }}">{{ Str::limit($risk->name, 40) }}</li>
                                                @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom row(s): same placeholders + a 5-col grid for both the labels and "Likelihood" -->
        <div class="flex mt-2">
            <!-- Placeholder for the Impact label column -->
            <div class="w-10"></div>
            <!-- Placeholder for row labels column -->
            <div class="w-10"></div>

            <!-- 5-col bottom grid -->
            <div class="flex-1">
                <div class="grid grid-cols-5 gap-0.5 text-center w-full">
                    <!-- First row: the 5 "Likelihood" labels -->
                    <div class="text-xs">Very Low</div>
                    <div class="text-xs">Low</div>
                    <div class="text-xs">Moderate</div>
                    <div class="text-xs">High</div>
                    <div class="text-xs">Very High</div>

                    <!-- Second row: leave columns 1,2 & 4,5 blank; put "Likelihood" in column 3 -->
                    <div></div>
                    <div></div>
                    <div class="text-sm font-bold">Likelihood</div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>
