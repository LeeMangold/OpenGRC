<x-filament-panels::page>
    {{-- Checklist Summary Infolist --}}
    {{ $this->checklistSummaryInfolist }}

    {{-- Approval Form --}}
    <x-filament::section>
        <form wire:submit="approve" class="fi-form grid gap-y-6">
            {{ $this->form }}

            <div class="flex gap-3 justify-end">
                @foreach ($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </form>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
