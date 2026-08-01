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
        ->call('loadDetails')
        ->assertSet('detailsLoaded', true)
        ->assertSee('Profiled request completed');
});

it('keeps every section panel mounted for client side navigation', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    $html = Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->call('loadDetails')
        ->html();

    expect($html)
        ->toContain('data-ndb-section-panel="overview"')
        ->toContain('data-ndb-section-panel="request"')
        ->toContain('data-ndb-section-panel="queries"')
        ->not->toContain('<template x-if="selected ===');
});

it('locks the server profile identifier', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('profileId', 'changed'))
        ->toThrow(Exception::class);
});

it('returns not found when deferred profile details have expired', function () {
    Livewire::test(DebugBar::class, ['profileId' => '00000000-0000-4000-8000-000000000000'])
        ->call('loadDetails')
        ->assertNotFound();
});
