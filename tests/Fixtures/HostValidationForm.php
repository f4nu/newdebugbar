<?php

namespace NewDebugBar\Tests\Fixtures;

use Livewire\Component;

/** Provides a host-owned form whose action fails Laravel validation. */
final class HostValidationForm extends Component
{
    public bool $dense = false;

    public string $email = 'not-an-email';

    public string $name = '';

    /** @var array<string, mixed> */
    public array $traveler = [
        'profile' => [
            'preferred_display_name' => '',
            'preferred_locale' => 'english',
        ],
        'contact' => [
            'primary_email_address' => 'also-not-an-email',
            'backup_email_address' => '',
        ],
        'emergency_contacts' => [
            ['phone_number' => 'abc'],
            ['phone_number' => ''],
        ],
        'itinerary' => [
            'days' => [[
                'accommodation' => ['confirmation_code' => 'x'],
                'activities' => [
                    ['start_time' => 'tomorrow morning'],
                    ['start_time' => '25:90'],
                ],
            ]],
        ],
        'billing' => [
            'address' => [
                'postal_code' => 'x',
                'country_code' => 'France',
            ],
        ],
    ];

    public function mount(bool $dense = false): void
    {
        $this->dense = $dense;
    }

    public function save(): void
    {
        if ($this->dense) {
            $this->validate([
                'email' => ['required', 'email', 'ends_with:@northstar.test'],
                'name' => ['required'],
                'traveler.profile.preferred_display_name' => ['required', 'min:3'],
                'traveler.profile.preferred_locale' => ['required', 'in:en,fr,de'],
                'traveler.contact.primary_email_address' => ['required', 'email', 'ends_with:@northstar.test'],
                'traveler.contact.backup_email_address' => ['required', 'email'],
                'traveler.emergency_contacts.0.phone_number' => ['required', 'regex:/^\\+[1-9][0-9]{7,14}$/'],
                'traveler.emergency_contacts.1.phone_number' => ['required', 'regex:/^\\+[1-9][0-9]{7,14}$/'],
                'traveler.itinerary.days.0.accommodation.confirmation_code' => ['required', 'min:6'],
                'traveler.itinerary.days.0.activities.0.start_time' => ['required', 'date_format:H:i'],
                'traveler.itinerary.days.0.activities.1.start_time' => ['required', 'date_format:H:i'],
                'traveler.billing.address.postal_code' => ['required', 'min:4'],
                'traveler.billing.address.country_code' => ['required', 'size:2'],
            ]);

            return;
        }

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
