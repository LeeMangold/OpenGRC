<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
