<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Storage\ProfileStore;

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

it('locks server-owned profile state', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('new-debug-bar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('profileId', 'changed'))
        ->toThrow(Exception::class);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('summary.status', 500))
        ->toThrow(Exception::class);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('detailsLoaded', true))
        ->toThrow(Exception::class);
});

it('returns not found when deferred profile details have expired', function () {
    Livewire::test(DebugBar::class, ['profileId' => '00000000-0000-4000-8000-000000000000'])
        ->call('loadDetails')
        ->assertNotFound();
});

it('summarizes warnings, slow queries, and duplicate sql', function () {
    $id = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'id' => $id,
        'environment' => 'testing',
        'metrics' => ['duration_ms' => 15.2, 'peak_memory_mb' => 8.5],
        'sections' => [
            'request' => [
                'label' => 'Request',
                'summary' => ['method' => 'POST', 'status' => 500],
                'payload' => ['path' => '/organizations'],
            ],
            'queries' => [
                'label' => 'Queries',
                'summary' => ['count' => 3, 'duration_ms' => 130.5],
                'payload' => ['items' => [
                    ['sql' => 'select * from users', 'duration_ms' => 120],
                    ['sql' => "select  *  from\nusers", 'duration_ms' => 5],
                    ['sql' => 'select * from clinics', 'duration_ms' => 5.5],
                ]],
            ],
            'exceptions' => [
                'label' => 'Exceptions',
                'summary' => ['count' => 1],
                'payload' => ['items' => []],
            ],
        ],
    ]);

    Livewire::test(DebugBar::class, ['profileId' => $id])
        ->assertSet('summary.method', 'POST')
        ->assertSet('summary.path', '/organizations')
        ->assertSet('summary.status', 500)
        ->assertSet('summary.warning', true)
        ->assertSet('summary.slow_query_count', 1)
        ->assertSet('summary.duplicate_query_count', 1)
        ->assertSet('summary.exception_count', 1);
});
