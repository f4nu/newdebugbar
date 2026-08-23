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
        'livewire/sections/http_client.blade.php',
    ] as $view) {
        $contents = file_get_contents($views.'/'.$view);

        expect($contents)
            ->toContain('<x-newdebugbar::filter-tabs')
            ->toContain('<x-newdebugbar::filter-tab');
    }
});

it('respects reduced motion for toolbar drag animations', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/newdebugbar.css');

    expect($css)
        ->toContain('.ndb-toolbar-draggable')
        ->toMatch('/@media \(prefers-reduced-motion: reduce\)[\s\S]*#newdebugbar \*[\s\S]*transition-duration: 0\.001ms !important;/');
});
