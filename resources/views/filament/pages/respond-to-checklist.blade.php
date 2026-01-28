<x-filament-panels::page>
    <form wire:submit="submit" class="fi-form grid gap-y-6">
        {{ $this->form }}

        <div class="flex gap-3 justify-end">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
