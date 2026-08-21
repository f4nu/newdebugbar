<?php

use Livewire\Component;

new class extends Component
{
    public string $status = 'Ready';

    public bool $enabled = true;
};
?>

<div data-testid="host-functional-status">{{ $status }}</div>
