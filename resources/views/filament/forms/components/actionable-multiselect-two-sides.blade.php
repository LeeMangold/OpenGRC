<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $statePath = $getStatePath();
        $options = $getOptionsForJs();
        $metadata = $getOptionsMetadata();
        $actions = $getActions();
        $dropdownActions = $getDropdownActions();
        $currentState = $getState() ?? [];
        if (!is_array($currentState)) {
            $currentState = [];
        }
        $currentStateStrings = array_map('strval', array_values($currentState));

        // Compute selectable and selected options on the server
        $selectableOptions = collect($options)->filter(function ($label, $key) use ($currentStateStrings) {
            return !in_array((string) $key, $currentStateStrings, true);
        })->toArray();

        $selectedOptions = collect($options)->filter(function ($label, $key) use ($currentStateStrings) {
            return in_array((string) $key, $currentStateStrings, true);
        })->toArray();

        // Get all option keys as strings for JavaScript
        $allOptionKeys = array_map('strval', array_keys($options));
        $selectableKeys = array_map('strval', array_keys($selectableOptions));
    @endphp

    <div
        class="flex flex-col w-full transition duration-75 text-sm"
        x-data="{
            metadata: @js($metadata),
            openDropdown: null,

            selectOption(value) {
                $wire.set('{{ $statePath }}', [...($wire.get('{{ $statePath }}') || []), String(value)]);
            },

            unselectOption(value) {
                const current = $wire.get('{{ $statePath }}') || [];
                $wire.set('{{ $statePath }}', current.filter(v => v !== String(value)));
            },

            selectAll(allKeys) {
                $wire.set('{{ $statePath }}', allKeys);
            },

            unselectAll() {
                $wire.set('{{ $statePath }}', []);
            },

            executeAction(actionName, actionConfig, count, selectableIds, metadata) {
                if (!selectableIds || selectableIds.length === 0) return;

                const currentState = $wire.get('{{ $statePath }}') || [];
                const meta = metadata || this.metadata || {};

                let idsToSelect = [];

                switch (actionConfig.type || actionName) {
                    case 'random':
                        idsToSelect = this.selectRandom(selectableIds, count);
                        break;
                    case 'randomUnassessed':
                        idsToSelect = this.selectRandomUnassessed(selectableIds, count, meta);
                        break;
                    case 'oldest':
                        idsToSelect = this.selectOldest(selectableIds, count, meta);
                        break;
                    case 'oldestPreviouslyAssessed':
                        idsToSelect = this.selectOldestPreviouslyAssessed(selectableIds, count, meta);
                        break;
                    default:
                        idsToSelect = this.selectRandom(selectableIds, count);
                }

                if (idsToSelect.length > 0) {
                    const newState = [...new Set([...currentState, ...idsToSelect])];
                    $wire.set('{{ $statePath }}', newState);
                }
            },

            selectRandom(selectableIds, count) {
                const shuffled = [...selectableIds].sort(() => Math.random() - 0.5);
                return shuffled.slice(0, Math.min(count, shuffled.length));
            },

            selectRandomUnassessed(selectableIds, count, metadata) {
                const unassessedValues = ['Not Assessed', 'UNKNOWN', 'Unknown', 'not_assessed', 'NOT_ASSESSED', null, undefined, ''];
                const unassessed = selectableIds.filter(id => {
                    const itemMeta = metadata[id] || metadata[String(id)] || {};
                    const effectiveness = itemMeta.effectiveness;
                    // Handle no metadata or no effectiveness
                    if (!effectiveness) return true;
                    // Handle enum object with value property
                    const effectivenessValue = typeof effectiveness === 'object' ? (effectiveness.value || effectiveness.name) : effectiveness;
                    return unassessedValues.includes(effectivenessValue);
                });

                if (unassessed.length === 0) return [];
                if (count <= 0) return unassessed;

                const shuffled = [...unassessed].sort(() => Math.random() - 0.5);
                return shuffled.slice(0, Math.min(count, shuffled.length));
            },

            selectOldest(selectableIds, count, metadata) {
                const neverAssessed = [];
                const assessed = [];

                selectableIds.forEach(id => {
                    const itemMeta = metadata[id] || metadata[String(id)];
                    const lastAssessed = itemMeta?.last_assessed_at;

                    if (!lastAssessed) {
                        neverAssessed.push(id);
                    } else {
                        assessed.push({ id, date: lastAssessed });
                    }
                });

                assessed.sort((a, b) => new Date(a.date) - new Date(b.date));
                const sorted = [...neverAssessed, ...assessed.map(a => a.id)];

                if (count <= 0) return sorted;
                return sorted.slice(0, count);
            },

            selectOldestPreviouslyAssessed(selectableIds, count, metadata) {
                const assessed = [];

                selectableIds.forEach(id => {
                    const itemMeta = metadata[id] || metadata[String(id)];
                    const lastAssessed = itemMeta?.last_assessed_at;

                    if (lastAssessed) {
                        assessed.push({ id, date: lastAssessed });
                    }
                });

                if (assessed.length === 0) return [];

                assessed.sort((a, b) => new Date(a.date) - new Date(b.date));
                const sorted = assessed.map(a => a.id);

                if (count <= 0) return sorted;
                return sorted.slice(0, count);
            },

            searchOptions(elementId, value) {
                const liList = document.querySelectorAll(`#${elementId} li`);
                liList.forEach(li => {
                    li.style.display = li.textContent.toLowerCase().includes(value.toLowerCase()) ? 'block' : 'none';
                });
            },

            toggleDropdown(name) {
                this.openDropdown = this.openDropdown === name ? null : name;
            },

            closeDropdowns() {
                this.openDropdown = null;
            }
        }"
        @click.away="closeDropdowns()"
    >
        {{-- Header Actions Bar --}}
        @if(count($actions) > 0 || count($dropdownActions) > 0)
            <div class="flex flex-wrap items-center gap-2 mb-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                @foreach($actions as $actionName => $action)
                    <button
                        type="button"
                        @click="executeAction('{{ $actionName }}', @js($action), {{ $action['count'] }}, @js($selectableKeys), @js($metadata))"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    >
                        @if($action['icon'])
                            <x-dynamic-component :component="$action['icon']" class="w-4 h-4" />
                        @endif
                        <span>{{ $action['label'] }}</span>
                    </button>
                @endforeach

                @foreach($dropdownActions as $actionName => $action)
                    <div class="relative">
                        <button
                            type="button"
                            @click="toggleDropdown('{{ $actionName }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            @if($action['icon'])
                                <x-dynamic-component :component="$action['icon']" class="w-4 h-4" />
                            @endif
                            <span>{{ $action['label'] }}</span>
                            <x-heroicon-m-chevron-down class="w-3 h-3 transition-transform" ::class="{ 'rotate-180': openDropdown === '{{ $actionName }}' }" />
                        </button>

                        <div
                            x-show="openDropdown === '{{ $actionName }}'"
                            x-transition
                            class="absolute left-0 z-50 mt-1 w-48 origin-top-left rounded-md bg-white dark:bg-gray-700 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                            style="display: none;"
                        >
                            <div class="py-1">
                                @foreach($action['options'] as $option)
                                    <button
                                        type="button"
                                        @click="executeAction('{{ $actionName }}', @js($action), {{ $option['count'] ?? 0 }}, @js($selectableKeys), @js($metadata)); closeDropdowns()"
                                        class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                                    >
                                        {{ $option['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Two-Sides Selector --}}
        <div class="flex w-full">
            {{-- Selectable Options (Left) --}}
            <div class="flex-1 border overflow-hidden rounded-lg shadow-sm bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                <p class="text-center w-full py-4 bg-gray-300 dark:bg-gray-600">
                    {{ $getSelectableLabel() }} ({{ count($selectableOptions) }})
                </p>
                <div class="p-2">
                    @if($isSearchable())
                        <input
                            placeholder="Search..."
                            class="w-full border-gray-300 border py-2 px-1 mb-2 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-100 dark:bg-gray-600 dark:border-gray-500"
                            @keyup="searchOptions('{{ str($statePath)->replace('.', '_') }}_selectable', $event.target.value)"
                        />
                    @endif
                    <ul class="h-48 overflow-y-auto" id="{{ str($statePath)->replace('.', '_') }}_selectable">
                        @foreach($selectableOptions as $key => $label)
                            <li
                                wire:key="selectable-{{ $key }}"
                                class="cursor-pointer p-1 hover:bg-primary-500 hover:text-white transition"
                                @click="selectOption('{{ $key }}')"
                            >{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Arrow Actions --}}
            <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
                <button type="button" @click="selectAll(@js($allOptionKeys))" class="cursor-pointer p-1 hover:bg-primary-500 group rounded" title="Select All">
                    <x-heroicon-o-chevron-double-right class="w-5 h-5 text-primary-500 group-hover:text-white"/>
                </button>
                <button type="button" @click="unselectAll()" class="cursor-pointer p-1 hover:bg-primary-500 group rounded" title="Unselect All">
                    <x-heroicon-o-chevron-double-left class="w-5 h-5 text-primary-500 group-hover:text-white"/>
                </button>
            </div>

            {{-- Selected Options (Right) --}}
            <div class="flex-1 border overflow-hidden rounded-lg shadow-sm bg-white dark:bg-gray-700 {{ count($selectedOptions) === 0 && $isRequired() ? 'border-danger-600 dark:border-danger-400' : 'border-gray-300 dark:border-gray-600' }}">
                <p class="text-center w-full py-4 rounded-t-lg bg-gray-300 dark:bg-gray-600">
                    {{ $getSelectedLabel() }} ({{ count($selectedOptions) }})
                </p>
                <div class="p-2">
                    @if($isSearchable())
                        <input
                            placeholder="Search..."
                            class="w-full border-gray-300 border py-2 px-1 mb-2 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-100 dark:bg-gray-600 dark:border-gray-500"
                            @keyup="searchOptions('{{ str($statePath)->replace('.', '_') }}_selected', $event.target.value)"
                        />
                    @endif
                    <ul class="h-48 overflow-y-auto" id="{{ str($statePath)->replace('.', '_') }}_selected">
                        @foreach($selectedOptions as $key => $label)
                            <li
                                wire:key="selected-{{ $key }}"
                                class="cursor-pointer p-1 hover:bg-primary-500 hover:text-white transition"
                                @click="unselectOption('{{ $key }}')"
                            >{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
