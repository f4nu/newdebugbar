<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\QueryExplainer;

it('offers runnable SQL and runs manual SQLite explain with the default bindings', function () {
    $response = $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();
    $id = $response->headers->get('X-NewDebugBar-Profile');
    $stored = app(ProfileStore::class)->get($id);
    $profile = app(ProfilePresenter::class)->present($stored);
    $query = $profile['sections']['queries']['payload']['items'][0];

    expect($query)
        ->binding_policy->toBe('full')
        ->bindings_complete->toBeTrue()
        ->source_preserved->toBeTrue()
        ->runnable_available->toBeTrue()
        ->runnable_sql->toContain('select 1 as number')
        ->and($profile['sections']['queries']['summary']['count'])->toBe(3);

    $result = app(QueryExplainer::class)->explain($query);

    expect($result)
        ->driver->toBe('sqlite')
        ->mode->toBe('EXPLAIN QUERY PLAN')
        ->rows->not->toBeEmpty();

    Livewire::test(DebugBar::class, ['profileId' => $id])
        ->call('loadDetails')
        ->call('explainQuery', 1)
        ->assertSet('queryExplains.1.driver', 'sqlite')
        ->assertSet('queryExplainErrors', [])
        ->assertDispatched('newdebugbar-query-explained', function (string $name, array $params): bool {
            return $name === 'newdebugbar-query-explained'
                && $params['execution'] === 1
                && $params['explain']['driver'] === 'sqlite'
                && $params['error'] === null;
        });
});

it('rejects unsafe incomplete and mutating explain requests before touching the database', function (array $query) {
    expect(fn () => app(QueryExplainer::class)->explain($query))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'safe binding policy' => [[
        'sql' => 'select ? as number',
        'bindings' => [1],
        'source_preserved' => true,
        'binding_policy' => 'safe',
        'bindings_complete' => false,
        'connection' => 'testing',
    ]],
    'multiple statements' => [[
        'sql' => 'select 1; delete from users',
        'bindings' => [],
        'source_preserved' => true,
        'binding_policy' => 'full',
        'bindings_complete' => true,
        'connection' => 'testing',
    ]],
    'write query' => [[
        'sql' => 'delete from users',
        'bindings' => [],
        'source_preserved' => true,
        'binding_policy' => 'full',
        'bindings_complete' => true,
        'connection' => 'testing',
    ]],
]);

it('shows bindings directly when there is no application stack', function () {
    $query = [
        'execution' => 1,
        'duration_ms' => 0.4,
        'slow' => false,
        'connection' => 'testing',
        'query_type' => 'read',
        'sql' => 'select ? as number',
        'normalized_sql' => 'select ? as number',
        'bindings' => [1],
        'stack' => [],
        'callsite' => null,
        'repeated' => false,
        'query_time_percent' => 100,
        'runnable_available' => false,
    ];

    $html = Blade::render(
        '<x-newdebugbar::query-execution :query="$query" identity="bindings-only" />',
        ['query' => $query],
    );

    expect($html)
        ->toContain('data-ndb-query-evidence-direct="bindings"')
        ->toContain('data-ndb-query-bindings-panel')
        ->not->toContain('data-ndb-query-tabs')
        ->not->toContain('role="tab"');
});
