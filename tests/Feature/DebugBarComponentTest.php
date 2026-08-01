<?php

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;

it('loads full profile details only after the inspector asks', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->assertSet('detailsLoaded', false)
        ->assertSee('New Debug Bar')
        ->call('loadDetails')
        ->assertSet('detailsLoaded', true)
        ->assertSee('Profiled request completed');
});

it('locks the server profile identifier', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('profileId', 'changed'))
        ->toThrow(Exception::class);
});

it('keeps Alpine visibility stronger than isolated Tailwind utilities', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->assertSee('x-show.important="! inspectorOpen"', false)
        ->assertSee('x-show.important="inspectorOpen"', false)
        ->assertSee('x-show.important="paletteOpen"', false);
});
