<?php

namespace NewDebugBar\Tests\Fixtures;

use Livewire\Component;

/** Provides a host-owned form whose action fails Laravel validation. */
final class HostValidationForm extends Component
{
    public string $email = 'not-an-email';

    public string $name = '';

    public function save(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'name' => ['required'],
        ]);
    }

    public function addManualError(): void
    {
        $this->addError('email', 'This email was rejected by the component.');
    }

    public function render(): string
    {
        return <<<'HTML'
            <form wire:submit="save" data-testid="host-validation-form" novalidate>
                <label>Email <input wire:model="email" type="email"></label>
                <label>Name <input wire:model="name" type="text"></label>
                <button type="submit">Save profile</button>
                @error('email') <p>{{ $message }}</p> @enderror
                @error('name') <p>{{ $message }}</p> @enderror
            </form>
            HTML;
    }
}
