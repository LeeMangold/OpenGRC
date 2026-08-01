<div class="border-t border-amber-400/20 bg-[#050d1f] text-xs">
    <div class="mx-auto flex flex-wrap items-center justify-between gap-4 px-6 py-3 text-gray-400">
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4 text-amber-400" />
            <span class="font-semibold uppercase tracking-wide text-gray-200">Data Integrity</span>
            <span>Verified &amp; Encrypted</span>
        </div>
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-circle-stack" class="h-4 w-4 text-amber-400" />
            <span class="font-semibold uppercase tracking-wide text-gray-200">Source</span>
            <span>FCC Systems (LMS, ULS, ASR)</span>
        </div>
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 text-amber-400" />
            <span class="font-semibold uppercase tracking-wide text-gray-200">Last Refresh</span>
            <span>{{ now()->diffForHumans() }}</span>
        </div>
        <div class="flex items-center gap-3 text-gray-300">
            <span class="font-semibold uppercase tracking-wide text-amber-400">Secure</span>
            <span class="text-gray-600">|</span>
            <span class="font-semibold uppercase tracking-wide text-amber-400">Compliant</span>
            <span class="text-gray-600">|</span>
            <span class="font-semibold uppercase tracking-wide text-amber-400">Trusted</span>
            <span class="ml-3 hidden md:inline text-gray-500">Built for FCC Compliance Professionals</span>
        </div>
    </div>
</div>
