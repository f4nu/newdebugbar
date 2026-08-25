<?php

use NewDebugBar\Tests\Support\ProjectFiles;

it('keeps every Blade view focused', function () {
    $oversizedViews = [];
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach (ProjectFiles::bladeFilesIn($views) as $file) {
        $lines = substr_count(file_get_contents($file->getPathname()), "\n") + 1;

        if ($lines > 500) {
            $oversizedViews[] = ProjectFiles::relativePath($file, $views).': '.$lines.' lines';
        }
    }

    expect($oversizedViews)->toBe([]);
});

it('keeps package interface text at a readable minimum size', function () {
    $undersizedText = [];
    $packageResources = dirname(__DIR__, 2).'/resources';

    foreach (ProjectFiles::bladeFilesIn($packageResources.'/views') as $file) {
        preg_match_all('/text-\\[(?<size>\\d+(?:\\.\\d+)?)px\\]/', file_get_contents($file->getPathname()), $matches);

        foreach ($matches['size'] as $size) {
            if ((float) $size < 11) {
                $undersizedText[] = ProjectFiles::relativePath($file, $packageResources.'/views').': '.$size.'px';
            }
        }
    }

    foreach (ProjectFiles::filesIn($packageResources.'/css') as $file) {
        preg_match_all('/font-size:\\s*(?<size>\\d+(?:\\.\\d+)?)px/', file_get_contents($file->getPathname()), $matches);

        foreach ($matches['size'] as $size) {
            if ((float) $size < 11) {
                $undersizedText[] = ProjectFiles::relativePath($file, $packageResources.'/css').': '.$size.'px';
            }
        }
    }

    expect($undersizedText)->toBe([]);
});

it('uses one popover surface for toolbar and inspector menus', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/mobile-toolbar-popover.blade.php',
        'components/query-actions.blade.php',
        'components/request-switcher.blade.php',
        'components/mail-actions.blade.php',
        'livewire/sections/views.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::popover-surface');
    }
});

it('uses one filter tab treatment across inspector sections', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/query-section.blade.php',
        'livewire/sections/authorization.blade.php',
        'livewire/sections/events.blade.php',
        'components/cache-controls.blade.php',
        'components/http-client-controls.blade.php',
    ] as $view) {
        $contents = file_get_contents($views.'/'.$view);

        expect($contents)
            ->toContain('<x-newdebugbar::filter-tabs')
            ->toContain('<x-newdebugbar::filter-tab');
    }

    foreach ([
        'components/cache-detail-tabs.blade.php',
        'components/http-client-detail-tabs.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-detail-tabs')
            ->toContain('<x-newdebugbar::filter-tab');
    }

    expect(file_get_contents($views.'/components/inspector-detail-tabs.blade.php'))
        ->toContain('<x-newdebugbar::filter-tabs');
});

it('composes the HTTP Client workspace from focused view components', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/http_client.blade.php');
    $workspace = file_get_contents($views.'/components/http-client-workspace.blade.php');
    $detail = file_get_contents($views.'/components/http-client-detail.blade.php');
    $controls = file_get_contents($views.'/components/http-client-controls.blade.php');
    $header = file_get_contents($views.'/components/http-client-header.blade.php');
    $request = file_get_contents($views.'/components/http-client-request-panel.blade.php');
    $response = file_get_contents($views.'/components/http-client-response-panel.blade.php');
    $source = file_get_contents($views.'/components/http-client-source-panel.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::http-client-workspace')
        ->toContain('<x-newdebugbar::http-client-empty')
        ->not->toContain('data-ndb-http-client-list');

    expect($workspace)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::http-client-controls')
        ->toContain('<x-newdebugbar::http-client-list-item')
        ->toContain('<x-newdebugbar::http-client-detail');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::http-client-header')
        ->toContain('<x-newdebugbar::http-client-detail-tabs')
        ->toContain('<x-newdebugbar::http-client-request-panel')
        ->toContain('<x-newdebugbar::http-client-response-panel')
        ->toContain('<x-newdebugbar::http-client-source-panel');

    expect($controls)
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->toContain('<x-newdebugbar::filter-tabs');

    expect($header)
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->toContain('<x-newdebugbar::http-client-method')
        ->toContain('<x-newdebugbar::inspector-action');

    expect($request)
        ->toContain('<x-newdebugbar::inspector-facts')
        ->toContain('<x-newdebugbar::inspector-action')
        ->toContain('<x-newdebugbar::inspector-evidence');

    expect($response)
        ->toContain('<x-newdebugbar::inspector-facts')
        ->toContain('<x-newdebugbar::inspector-definition-list')
        ->toContain('<x-newdebugbar::inspector-evidence')
        ->toContain('<x-newdebugbar::http-client-no-response');

    expect($source)
        ->toContain('<x-newdebugbar::inspector-source-fact')
        ->toContain('<x-newdebugbar::inspector-stack');
});

it('composes the Cache workspace from the shared inspector components', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/cache.blade.php');
    $workspace = file_get_contents($views.'/components/cache-workspace.blade.php');
    $detail = file_get_contents($views.'/components/cache-detail.blade.php');
    $controls = file_get_contents($views.'/components/cache-controls.blade.php');
    $header = file_get_contents($views.'/components/cache-header.blade.php');
    $overview = file_get_contents($views.'/components/cache-overview-panel.blade.php');
    $raw = file_get_contents($views.'/components/cache-raw-panel.blade.php');
    $source = file_get_contents($views.'/components/cache-source-panel.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::cache-workspace')
        ->toContain('<x-newdebugbar::cache-empty')
        ->not->toContain('data-ndb-cache-list');

    expect($workspace)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::cache-controls')
        ->toContain('<x-newdebugbar::cache-list-item')
        ->toContain('<x-newdebugbar::cache-detail');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::cache-header')
        ->toContain('<x-newdebugbar::cache-detail-tabs')
        ->toContain('<x-newdebugbar::cache-overview-panel')
        ->toContain('<x-newdebugbar::cache-raw-panel')
        ->toContain('<x-newdebugbar::cache-source-panel');

    expect($controls)
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::filter-tabs')
        ->toContain('variant="segmented"')
        ->not->toContain('<select')
        ->not->toContain('cacheSort');

    expect($header)
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->not->toContain('font-mono');

    expect($overview)
        ->toContain('<x-newdebugbar::cache-overview-facts')
        ->toContain('<x-newdebugbar::inspector-definition-list')
        ->not->toContain('What happened')
        ->not->toContain('Check next');

    expect($raw)->toContain('<x-newdebugbar::inspector-evidence');
    expect($source)
        ->toContain('<x-newdebugbar::inspector-source-fact')
        ->toContain('<x-newdebugbar::inspector-stack');
});

it('uses one calm source presentation across inspector sections', function () {
    $resources = dirname(__DIR__, 2).'/resources';
    $views = $resources.'/views';

    foreach ([
        'components/cache-overview-facts.blade.php',
        'components/mail-header.blade.php',
        'components/notification-header.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-source-link');
    }

    foreach ([
        'components/cache-source-panel.blade.php',
        'components/http-client-source-panel.blade.php',
        'components/mail-source-panel.blade.php',
        'components/notification-source-panel.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-source-fact')
            ->toContain('<x-newdebugbar::inspector-stack');
    }

    expect(file_get_contents($views.'/components/mail-message-details.blade.php'))
        ->toContain('<x-newdebugbar::mail-source-panel')
        ->not->toContain('data-ndb-mail-detail-panel="source"');

    expect(file_get_contents($resources.'/css/newdebugbar.css'))
        ->toContain('@fontsource-variable/jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2')
        ->toContain('--font-mono: "JetBrains Mono Variable"')
        ->toContain('font-variant-ligatures: contextual common-ligatures discretionary-ligatures');
});

it('uses the top-only frame across edge-to-edge inspector workspaces', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/cache-workspace.blade.php',
        'components/http-client-workspace.blade.php',
        'livewire/sections/mail.blade.php',
        'livewire/sections/notifications.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-workspace')
            ->toContain('frame="top"');
    }
});

it('uses the shared section heading hierarchy in the inspector shell', function () {
    $inspector = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/inspector.blade.php');

    expect($inspector)
        ->toContain('<x-newdebugbar::section-heading>')
        ->not->toContain('<header data-ndb-section-header');
});

it('uses layout instead of punctuation to separate interface facts', function () {
    $resources = dirname(__DIR__, 2).'/resources';
    $offenders = [];

    foreach (ProjectFiles::filesIn($resources) as $file) {
        $contents = file_get_contents($file->getPathname());

        foreach (['•', '·', '&bull;', '&middot;', '&#8226;', '&#183;', '&#x2022;', '&#xB7;'] as $separator) {
            if (str_contains($contents, $separator)) {
                $offenders[] = ProjectFiles::relativePath($file, $resources).': '.$separator;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('respects reduced motion for toolbar drag animations', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/newdebugbar.css');

    expect($css)
        ->toContain('.ndb-toolbar-draggable')
        ->toMatch('/@media \(prefers-reduced-motion: reduce\)[\s\S]*#newdebugbar \*[\s\S]*transition-duration: 0\.001ms !important;/');
});
