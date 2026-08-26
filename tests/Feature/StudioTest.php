<?php

use NewDebugBar\Presentation\StudioCatalog;
use NewDebugBar\Storage\ProfileStore;

it('serves a component-first Studio without profiling package traffic', function () {
    $response = $this->get(route('newdebugbar.studio'));
    $html = $response->getContent();

    $response
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile')
        ->assertSeeText('Search Field')
        ->assertDontSeeText('Why it exists')
        ->assertDontSeeText('What this is')
        ->assertDontSeeText('Every reusable interface part')
        ->assertSeeText('Desktop')
        ->assertSeeText('Mobile')
        ->assertSee('data-ndb-studio-frame', false);

    expect(substr_count($html, 'data-ndb-studio-navigation-group='))
        ->toBe(count(StudioCatalog::navigationGroups()))
        ->and(substr_count($html, 'data-ndb-studio-component-link='))
        ->toBe(count(StudioCatalog::components()))
        ->and(substr_count($html, 'id="newdebugbar"'))
        ->toBe(1)
        ->and(app(ProfileStore::class)->recent())
        ->toBe([]);
});

it('renders exactly one focused demo for every catalog component', function () {
    foreach (StudioCatalog::components() as $component => $metadata) {
        app('livewire')->flushState();

        $response = $this->get(route('newdebugbar.studio.preview', [
            'component' => $component,
            'theme' => 'dark',
        ]));

        $response
            ->assertOk()
            ->assertHeaderMissing('X-NewDebugBar-Profile')
            ->assertSee('data-ndb-studio-preview="'.$component.'"', false)
            ->assertSee('data-ndb-studio-catalog="'.$metadata['group'].'"', false)
            ->assertSee('data-ndb-theme="dark"', false)
            ->assertSee('data-ndb-studio-demo="'.$component.'"', false);

        $html = $response->getContent();

        expect(substr_count($html, 'id="newdebugbar"'))->toBe(1)
            ->and(substr_count($html, 'data-ndb-studio-demo='))->toBe(1)
            ->and(strpos($html, '/__newdebugbar/assets/newdebugbar.js'))
            ->toBeLessThan(strpos($html, ' data-csrf='));
    }

    expect(app(ProfileStore::class)->recent())->toBe([]);
});

it('gives every component a canonical detail page and preserves bounded preview widths', function () {
    $response = $this->get(route('newdebugbar.studio.component', [
        'component' => 'inspector-detail-header',
        'theme' => 'dark',
        'width' => 712,
    ]));

    $response
        ->assertOk()
        ->assertSeeText('Detail Header')
        ->assertSeeText('Inspector layout')
        ->assertDontSeeText('Why it exists')
        ->assertSee('data-ndb-studio-component-link="inspector-detail-header"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('width: 712px', false)
        ->assertSee(route('newdebugbar.studio.preview', [
            'component' => 'inspector-detail-header',
            'theme' => 'dark',
        ]), false);

    $this->get(route('newdebugbar.studio.component', [
        'component' => 'search-field',
        'width' => 12,
    ]))->assertSee('width: 320px', false);

    $this->get(route('newdebugbar.studio.component', [
        'component' => 'search-field',
        'width' => 5000,
    ]))->assertSee('width: 1440px', false);
});

it('rejects unknown Studio components and previews', function () {
    foreach (['unknown', 'cache-header', 'notification-header', 'corner-toolbar'] as $component) {
        $this->get(route('newdebugbar.studio.component', ['component' => $component]))->assertNotFound();
        $this->get(route('newdebugbar.studio.preview', ['component' => $component]))->assertNotFound();
    }
});
