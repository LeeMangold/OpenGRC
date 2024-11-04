<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()"/>
        <x-filament-actions::modals/>
    </x-filament-panels::form>
</x-filament-panels::page>
