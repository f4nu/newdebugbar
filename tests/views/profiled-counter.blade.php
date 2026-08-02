<div data-testid="profiled-counter">
    <p data-testid="profiled-count">{{ $count }}</p>
    <input wire:model="name" data-testid="profiled-name">
    <button type="button" wire:click="increment" data-testid="profiled-increment">Increment</button>
    <button type="button" wire:click="save" data-testid="profiled-save">Save</button>
</div>
