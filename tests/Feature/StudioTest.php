<?php

use NewDebugBar\Presentation\StudioCatalog;
use NewDebugBar\Storage\ProfileStore;

it('serves a local component studio without profiling package traffic', function () {
    $response = $this->get(route('newdebugbar.studio'));

    $response
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile')
        ->assertSeeText('Studio')
        ->assertSeeText('Desktop')
        ->assertSeeText('Mobile')
        ->assertSee('data-ndb-studio-frame', false);

    expect(substr_count($response->getContent(), 'data-ndb-studio-family='))
        ->toBe(count(StudioCatalog::groups()))
        ->and(substr_count($response->getContent(), 'id="newdebugbar"'))
        ->toBe(1)
        ->and(app(ProfileStore::class)->recent())
        ->toBe([]);
});

it('renders every catalog family as a bounded component preview', function () {
    foreach (StudioCatalog::groups() as $slug => $group) {
        app('livewire')->flushState();

        $response = $this->get(route('newdebugbar.studio', [
            'preview' => $slug,
            'theme' => 'dark',
        ]));

        $response
            ->assertOk()
            ->assertHeaderMissing('X-NewDebugBar-Profile')
            ->assertSee('data-ndb-studio-catalog="'.$slug.'"', false)
            ->assertSee('data-ndb-theme="dark"', false);

        $html = $response->getContent();

        expect(substr_count($html, 'id="newdebugbar"'))->toBe(1)
            ->and(substr_count($html, 'data-ndb-studio-demo='))->toBe(count($group['components']))
            ->and(strpos($html, '/__newdebugbar/assets/newdebugbar.js'))
            ->toBeLessThan(strpos($html, ' data-csrf='));

        foreach (array_keys($group['components']) as $component) {
            expect($html)->toContain('data-ndb-studio-demo="'.$component.'"');
        }
    }

    expect(app(ProfileStore::class)->recent())->toBe([]);
});

it('rejects unknown Studio families and preview groups', function () {
    $this->get(route('newdebugbar.studio', ['group' => 'unknown']))->assertNotFound();
    $this->get(route('newdebugbar.studio', ['preview' => 'unknown']))->assertNotFound();
});
