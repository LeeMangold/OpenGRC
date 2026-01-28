@props([
    'signature' => null,
    'signerName' => null,
    'signedAt' => null,
])

@if($signature)
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 max-w-md">
    {{-- Signature Box --}}
    <div class="border-b-2 border-gray-400 dark:border-gray-500 pb-3 mb-3">
        <div class="text-2xl font-signature text-gray-900 dark:text-gray-100 italic" style="font-family: 'Brush Script MT', 'Segoe Script', 'Bradley Hand', cursive;">
            {{ $signature }}
        </div>
    </div>

    {{-- Signature Details --}}
    <div class="flex flex-col gap-1 text-xs text-gray-600 dark:text-gray-400">
        <div class="flex items-center gap-2">
            <x-heroicon-m-user class="w-3.5 h-3.5" />
            <span>{{ $signerName }}</span>
        </div>
        <div class="flex items-center gap-2">
            <x-heroicon-m-calendar class="w-3.5 h-3.5" />
            <span>{{ $signedAt?->format('M j, Y \a\t g:i A') }}</span>
        </div>
        <div class="flex items-center gap-2 text-success-600 dark:text-success-400 mt-1">
            <x-heroicon-m-shield-check class="w-3.5 h-3.5" />
            <span class="font-medium">Digitally signed</span>
        </div>
    </div>
</div>
@endif
