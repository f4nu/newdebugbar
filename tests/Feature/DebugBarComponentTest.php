<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

it('presents a corrupted partial Livewire section as unknown evidence', function () {
    $profile = app(ProfilePresenter::class)->present([
        'metrics' => [],
        'sections' => [
            'livewire' => [
                'summary' => 'corrupted',
                'payload' => 'corrupted',
            ],
        ],
    ]);

    expect($profile['sections']['livewire'])
        ->label->toBe('Livewire')
        ->summary->count->toBe(0)
        ->payload->presentation->headline->title->toBe('Livewire exchange')
        ->payload->presentation->outcome->title->toBe('Result is not fully known')
        ->payload->presentation->events->toBe([]);
});

it('loads full profile details only after the inspector asks', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('newdebugbar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    $component = Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->assertSet('detailsLoaded', false)
        ->call('loadDetails')
        ->assertSet('detailsLoaded', true)
        ->assertDispatched('newdebugbar-content-updated')
        ->assertSee('Profiled request completed')
        ->assertSeeHtml('data-ndb-lifecycle-scope');

    expect(preg_replace('/\s+/', ' ', $component->html()))
        ->toContain('Early Laravel bootstrap is not measured.');
});

it('locks server-owned profile state', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $file = File::files(config('newdebugbar.storage.path'))[0];
    $profile = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('profileId', 'changed'))
        ->toThrow(Exception::class);

    expect(fn () => Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->set('currentProfileId', 'changed'))
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
                'payload' => [
                    'method' => 'POST',
                    'status' => 500,
                    'path' => '/organizations',
                    'route' => 'organizations.store',
                    'action' => 'OrganizationController@store',
                ],
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
        ->assertSet('summary.id', $id)
        ->assertSet('summary.environment', 'testing')
        ->assertSet('summary.method', 'POST')
        ->assertSet('summary.path', '/organizations')
        ->assertSet('summary.status', 500)
        ->assertSet('summary.warning', true)
        ->assertSet('summary.peak_memory_mb', 8.5)
        ->assertSet('summary.query_time_ms', 130.5)
        ->assertSet('summary.slow_query_count', 1)
        ->assertSet('summary.repeated_pattern_count', 1)
        ->assertSet('summary.exception_count', 1)
        ->assertSet('detailsLoaded', true)
        ->assertSet('profile.findings.0.summary', 'The request returned HTTP 500.');
});

it('marks active, quiet, truncated, and incomplete sections for disclosure', function () {
    $id = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'id' => $id,
        'environment' => 'testing',
        'metrics' => ['duration_ms' => 15.2, 'peak_memory_mb' => 8.5],
        'sections' => [
            'overview' => ['label' => 'Overview', 'summary' => [], 'payload' => []],
            'request' => [
                'label' => 'Request',
                'summary' => ['method' => 'GET', 'status' => 200],
                'payload' => [
                    'method' => 'GET',
                    'status' => 200,
                    'path' => '/organizations',
                    'route' => null,
                    'action' => null,
                ],
            ],
            'queries' => [
                'label' => 'Queries',
                'summary' => ['count' => 0, 'duration_ms' => 0],
                'payload' => ['items' => []],
            ],
            'views' => [
                'label' => 'Views',
                'summary' => ['count' => 2, 'retained_count' => 0, 'dropped_count' => 2],
                'payload' => ['items' => []],
            ],
            'logs' => [
                'label' => 'Logs',
                'summary' => ['count' => 0],
                'payload' => ['items' => []],
            ],
            'exceptions' => [
                'label' => 'Exceptions',
                'summary' => ['count' => 0],
                'payload' => ['items' => []],
            ],
        ],
    ]);

    $component = Livewire::test(DebugBar::class, ['profileId' => $id])
        ->assertSet('summary.warning', true)
        ->assertSet('summary.sections', function (array $sections): bool {
            $sections = collect($sections)->keyBy('key');

            return $sections['overview']['active'] === true
                && $sections['request']['active'] === true
                && $sections['queries']['active'] === false
                && $sections['logs']['active'] === false
                && $sections['exceptions']['active'] === false
                && $sections['views']['active'] === true
                && $sections['views']['attention'] === true
                && $sections['views']['truncated'] === true
                && $sections['views']['finding_count'] === 1
                && $sections['timeline']['active'] === true
                && $sections['timeline']['attention'] === true
                && $sections['timeline']['incomplete'] === true
                && $sections['history']['active'] === true;
        })
        ->call('loadDetails')
        ->assertDontSeeHtml('data-ndb-findings')
        ->assertSeeHtml('data-ndb-collection-status="views"')
        ->assertSee('Showing 0 of 2 views.')
        ->assertSeeHtml('data-ndb-timeline-incomplete');

    expect(preg_replace('/\s+/', ' ', $component->html()))
        ->toContain('Timeline incomplete: 2 source events were omitted.');
});

it('marks secondary query transaction omissions as truncated', function () {
    $id = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'id' => $id,
        'environment' => 'testing',
        'metrics' => ['duration_ms' => 10, 'peak_memory_mb' => 8],
        'sections' => [
            'request' => [
                'label' => 'Request',
                'summary' => ['method' => 'GET', 'status' => 200],
                'payload' => ['method' => 'GET', 'status' => 200, 'path' => '/', 'route' => null, 'action' => null],
            ],
            'queries' => [
                'label' => 'Queries',
                'summary' => [
                    'count' => 0,
                    'duration_ms' => 0,
                    'transaction_count' => 3,
                    'transaction_retained_count' => 1,
                    'transaction_dropped_count' => 2,
                    'truncated' => true,
                ],
                'payload' => [
                    'items' => [],
                    'transactions' => [['kind' => 'begin']],
                ],
            ],
            'exceptions' => ['label' => 'Exceptions', 'summary' => ['count' => 0], 'payload' => ['items' => []]],
        ],
    ]);

    $component = Livewire::test(DebugBar::class, ['profileId' => $id])
        ->assertSet('summary.sections', function (array $sections): bool {
            $queries = collect($sections)->firstWhere('key', 'queries');

            return $queries['active'] === true
                && $queries['attention'] === true
                && $queries['truncated'] === true;
        })
        ->call('loadDetails')
        ->assertSeeHtml('data-ndb-collection-status="query-transactions"');

    expect(preg_replace('/\s+/', ' ', $component->html()))
        ->toContain('Showing 1 of 3 query transaction events.');
});

it('uses the shared presenter for deferred query details and findings', function () {
    $id = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'id' => $id,
        'metrics' => ['duration_ms' => 100],
        'sections' => [
            'request' => ['label' => 'Request', 'summary' => ['method' => 'GET', 'status' => 200], 'payload' => [
                'method' => 'GET',
                'status' => 200,
                'path' => '/',
                'route' => null,
                'action' => null,
            ]],
            'queries' => ['label' => 'Queries', 'summary' => ['count' => 2, 'duration_ms' => 10], 'payload' => ['items' => [
                ['sql' => 'select ?', 'bindings' => [1], 'duration_ms' => 5, 'connection' => 'testing'],
                ['sql' => 'select ?', 'bindings' => [2], 'duration_ms' => 5, 'connection' => 'testing'],
            ]]],
            'exceptions' => ['label' => 'Exceptions', 'summary' => ['count' => 0], 'payload' => ['items' => []]],
        ],
    ]);

    Livewire::test(DebugBar::class, ['profileId' => $id])
        ->call('loadDetails')
        ->assertSet('profile.sections.queries.summary.repeated_pattern_count', 1)
        ->assertSet('profile.sections.queries.payload.items.0.repeated_count', 2)
        ->assertSet('profile.findings.0.rule_id', 'query.repeated');
});

it('loads retained history and compares requests from the same path', function () {
    $firstId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $currentId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $currentId])
        ->assertSet('summary.sections', function (array $sections): bool {
            $keys = collect($sections)->pluck('key');

            return $keys->filter(fn (string $key): bool => $key === 'history')->count() === 1
                && $keys->contains('timeline');
        })
        ->call('loadDetails')
        ->assertSet('history.0.is_current', true)
        ->assertSet('history.0.is_selected', true)
        ->assertSet('history.1.comparable', true)
        ->call('compareWith', $firstId)
        ->assertSet('comparisonProfileId', $firstId)
        ->assertSet('comparison.path', '/profiled')
        ->assertSet('comparison.metrics.0.key', 'duration_ms')
        ->assertSee('Compare requests')
        ->assertSee('Earlier request:')
        ->assertSee('Current request:')
        ->call('clearComparison')
        ->assertSet('comparisonProfileId', null)
        ->assertSet('comparison', []);
});

it('adds a discovered background profile to history without switching profiles', function () {
    $currentId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $backgroundId = $this->getJson('/api/plain-json')
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $currentId])
        ->call('loadDetails')
        ->call('discoverProfile', $backgroundId)
        ->assertSet('profileId', $currentId)
        ->assertSet('discoveredProfileId', $backgroundId)
        ->assertSet('history.0.is_current', true)
        ->assertSet('history.1.id', $backgroundId)
        ->assertSet('history.1.path', '/api/plain-json')
        ->assertDispatched('newdebugbar-content-updated');
});

it('rejects comparisons from a different path', function () {
    $currentId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $otherId = $this->get('/profiled-next', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $currentId])
        ->call('loadDetails')
        ->call('compareWith', $otherId)
        ->assertStatus(422);
});

it('switches to an exact retained application profile', function () {
    $firstId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $nextId = $this->get('/profiled-next', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $firstId])
        ->call('loadDetails')
        ->assertSet('detailsLoaded', true)
        ->call('switchProfile', $nextId)
        ->assertSet('profileId', $nextId)
        ->assertSet('currentProfileId', $nextId)
        ->assertSet('summary.path', '/profiled-next')
        ->assertSet('detailsLoaded', false)
        ->assertSet('history', [])
        ->assertDispatched('newdebugbar-profile-switched');
});

it('opens any retained profile without losing the current foreground request', function () {
    $currentId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $backgroundId = $this->getJson('/api/plain-json')
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $currentId])
        ->call('selectProfile', $backgroundId)
        ->assertSet('profileId', $backgroundId)
        ->assertSet('currentProfileId', $currentId)
        ->assertSet('summary.path', '/api/plain-json')
        ->assertSet('summary.is_current_profile', false)
        ->call('loadDetails')
        ->assertSet('history.0.id', $currentId)
        ->assertSet('history.0.is_current', true)
        ->assertSet('history.1.id', $backgroundId)
        ->assertSet('history.1.is_selected', true)
        ->call('returnToCurrent')
        ->assertSet('profileId', $currentId)
        ->assertSet('currentProfileId', $currentId)
        ->assertSet('summary.is_current_profile', true)
        ->assertDispatched('newdebugbar-profile-switched');
});
