<?php

use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\QueryExplainer;

it('offers runnable SQL and runs manual SQLite explain only with complete full bindings', function () {
    config()->set('newdebugbar.collection.query_bindings', 'full');
    $this->app->forgetInstance(ProfileManager::class);
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
        ->assertSet('queryExplainErrors', []);
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
