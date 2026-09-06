<x-filament-panels::page>
    <form wire:submit="save" class="fi-form" style="display:flex;flex-direction:column;gap:1.5rem;">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">Save settings</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
