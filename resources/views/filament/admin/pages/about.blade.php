<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Application Information Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4">
                <div class="fi-section-header">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Application Information
                    </h3>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Application Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">OpenGRC</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <span class="font-mono">{{ $version['display'] }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Git Branch</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <span class="font-mono">{{ $version['branch'] }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest Commit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <span class="font-mono">{{ $version['commit'] }}</span>
                            @if($version['date'])
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ $version['date'] }})</span>
                            @endif
                        </dd>
                    </div>
                    @if($version['tag'])
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest Tag</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">
                                {{ $version['tag'] }}
                            </span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Framework</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">Laravel {{ app()->version() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ phpversion() }}</dd>
                    </div>
                </dl>
                
                <div class="mt-6">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        OpenGRC is a cyber Governance, Risk, and Compliance (GRC) web application built for small businesses and teams. 
                        It provides tools for security program management, compliance framework imports, audits, and reporting.
                    </dd>
                </div>
            </div>
        </div>

        {{-- Software License Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4">
                <div class="fi-section-header">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Software License
                    </h3>
                    <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                        This software is licensed under {{ $license['short_type'] }}
                    </p>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20">
                            {{ $license['short_type'] }}
                        </span>
                        <a href="{{ $license['url'] }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            View Full License →
                        </a>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $license['type'] }}
                    </p>
                </div>
                
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $license['text'] }}</pre>
                </div>
            </div>
        </div>

        {{-- SBOM Section --}}
        @if($sbom['exists'])
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4">
                <div class="fi-section-header">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Software Bill of Materials (SBOM)
                    </h3>
                    <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                        Detailed inventory of software components and dependencies
                    </p>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Format</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $sbom['format'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">File Size</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $sbom['readable_size'] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Modified</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $sbom['modified'] }}</dd>
                    </div>
                </dl>
                
                <div class="flex items-center gap-3">
                    <x-filament::button 
                        color="primary" 
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="downloadSbom"
                    >
                        Download SBOM
                    </x-filament::button>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        JSON format compatible with CycloneDX tools
                    </span>
                </div>
            </div>
        </div>
        @else
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No SBOM Available</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Software Bill of Materials file not found in this installation.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- System Information Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4">
                <div class="fi-section-header">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        System Information
                    </h3>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Environment</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('app.env') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timezone</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('app.timezone') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('database.default') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Driver</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('cache.default') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-filament-panels::page>