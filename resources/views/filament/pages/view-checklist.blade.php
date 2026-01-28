<x-filament-panels::page>
    {{-- Checklist Details Infolist --}}
    @if ($this->hasInfolist())
        {{ $this->infolist }}
    @else
        {{ $this->form }}
    @endif

    {{-- Checklist Responses Section --}}
    @if($this->record->answers->count() > 0)
        <div class="mt-6">
            <x-checklist-responses :survey="$this->record" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
