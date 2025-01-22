@php
    use App\Filament\Resources\RiskResource\Widgets\InherentRisk;
@endphp

<x-filament-widgets::widget>
    <x-filament::card>
        <div class="bg-red-200 bg-red-500 bg-orange-200 bg-orange-500"></div>
        <div class="bg-green-200 bg-green-500 bg-yellow-200 bg-yellow-500"></div>
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
        <div class="flex h-[300px]">
            <!-- Narrow column for rotated "Impact" label -->
            <div style="width: 0;" class="flex items-center justify-center flex-none">
                <div class="transform -rotate-90 text-sm font-bold leading-none">
                    Impact
                </div>
            </div>

            <!-- Row labels + main grid -->
            <div class="flex-1 flex items-start">
                <!-- Row labels column -->
                <div class="w-20 flex flex-col gap-0.5">
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Very High</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">High</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Moderate</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Low</div>
                    <div class="h-[60px] flex items-center justify-end text-xs p-1">Very Low</div>
                </div>

                <!-- 5-col risk map grid -->
                <div class="flex-1">
                    <div class="grid grid-cols-5 gap-0.5 h-full w-full">
                        @foreach (array_reverse($grid) as $impactIndex => $impactRow)
                            @foreach ($impactRow as $likelihoodIndex => $count)
                                @php
                                    $colorWeight = 200;
                                    if($count > 0) {
                                        $colorWeight = 500;
                                    }
                                    $colorClass = \App\Filament\Resources\RiskResource::getRiskColor($likelihoodIndex + 1, sizeof($grid) - $impactIndex, $colorWeight);
                                @endphp

                                <div
                                        class="text-center flex items-center justify-center {{ $colorClass }}"
                                        style="height: 60px;"
                                        x-data="{ show: false }"
                                        @mouseenter="show = true"
                                        @mouseleave="show = false"
                                >
                                    @if ($count > 0)
                                        <div class="font-extrabold relative">
                                            {{ $count }}
                                            <div
                                                    x-show="show"
                                                    x-cloak
                                                    class="absolute z-10 bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2 shadow-lg"
                                            >
                                                {{ $count }} Items
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

        <!-- Bottom row(s): same placeholders + a 5-col grid for both the labels and “Likelihood” -->
        <div class="flex mt-2">
            <!-- Placeholder for the Impact label column -->
            <div class="w-10"></div>
            <!-- Placeholder for row labels column -->
            <div class="w-20"></div>

            <!-- 5-col bottom grid -->
            <div class="flex-1">
                <div class="grid grid-cols-5 gap-0.5 text-center w-full">
                    <!-- First row: the 5 “Likelihood” labels -->
                    <div class="text-xs">Very Low</div>
                    <div class="text-xs">Low</div>
                    <div class="text-xs">Moderate</div>
                    <div class="text-xs">High</div>
                    <div class="text-xs">Very High</div>

                    <!-- Second row: leave columns 1,2 & 4,5 blank; put “Likelihood” in column 3 -->
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
