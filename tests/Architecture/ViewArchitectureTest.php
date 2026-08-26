<?php

use NewDebugBar\Presentation\StudioCatalog;
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

it('catalogs only canonical shared component families in Studio', function () {
    $root = dirname(__DIR__, 2);
    $componentDirectory = $root.'/resources/views/components';
    $demoDirectory = $root.'/resources/views/studio/demos';
    $groups = StudioCatalog::groups();
    $kinds = StudioCatalog::kinds();
    $navigationGroups = StudioCatalog::navigationGroups();
    $components = StudioCatalog::components();
    $catalogPages = array_keys($components);
    $publicComponents = StudioCatalog::publicComponents();
    $privateComponentsByOwner = StudioCatalog::privateComponents();
    $privateComponents = collect($privateComponentsByOwner)->flatten()->all();
    $kindComponents = collect($kinds)->flatMap(fn (array $kind): array => $kind['components'])->all();
    $navigationComponents = collect($navigationGroups)
        ->flatMap(fn (array $group): array => $group['components'])
        ->all();
    $viewComponents = collect(ProjectFiles::bladeFilesIn($componentDirectory))
        ->map(fn (SplFileInfo $file): string => str_replace('.blade.php', '', $file->getFilename()))
        ->sort()
        ->values()
        ->all();
    $demoGroups = collect(ProjectFiles::bladeFilesIn($demoDirectory))
        ->map(fn (SplFileInfo $file): string => str_replace('.blade.php', '', $file->getFilename()))
        ->sort()
        ->values()
        ->all();
    $documentedComponents = file_get_contents($root.'/.agents/skills/craft-newdebugbar-ui/references/components.md');

    expect($catalogPages)->toHaveCount(count(array_unique($catalogPages)))
        ->and($publicComponents)->toHaveCount(count(array_unique($publicComponents)))
        ->and($privateComponents)->toHaveCount(count(array_unique($privateComponents)))
        ->and($kindComponents)->toHaveCount(count(array_unique($kindComponents)))
        ->and($navigationComponents)->toHaveCount(count(array_unique($navigationComponents)))
        ->and($kindComponents)->toHaveCount(count($catalogPages))
        ->and($navigationComponents)->toHaveCount(count($catalogPages));

    $registeredComponents = [...$publicComponents, ...$privateComponents];
    sort($registeredComponents);

    expect($registeredComponents)->toBe($viewComponents);

    sort($catalogPages);
    sort($kindComponents);
    sort($navigationComponents);

    expect($kindComponents)->toBe($catalogPages)
        ->and($navigationComponents)->toBe($catalogPages);

    foreach ($publicComponents as $publicComponent) {
        expect($viewComponents)->toContain($publicComponent)
            ->and($documentedComponents)->toContain('`'.$publicComponent.'`');

        $contents = file_get_contents($componentDirectory.'/'.$publicComponent.'.blade.php');
        preg_match_all('/<x-newdebugbar::(?<component>[a-z0-9-]+)/', $contents, $dependencies);

        foreach (array_unique($dependencies['component']) as $dependency) {
            expect(
                $publicComponents,
                sprintf('Public component [%s] depends on private component [%s].', $publicComponent, $dependency),
            )->toContain($dependency);
        }
    }

    $privateDependencyViolations = [];

    foreach ($privateComponentsByOwner as $owner => $ownedComponents) {
        expect($owner)->not->toBeEmpty()
            ->and($ownedComponents)->not->toBeEmpty();

        foreach ($ownedComponents as $ownedComponent) {
            $contents = file_get_contents($componentDirectory.'/'.$ownedComponent.'.blade.php');
            preg_match_all('/<x-newdebugbar::(?<component>[a-z0-9-]+)/', $contents, $dependencies);

            foreach (array_unique($dependencies['component']) as $dependency) {
                if (! in_array($dependency, $publicComponents, true) && ! in_array($dependency, $ownedComponents, true)) {
                    $privateDependencyViolations[] = sprintf(
                        'Private %s component [%s] depends on component [%s] owned by another product area.',
                        $owner,
                        $ownedComponent,
                        $dependency,
                    );
                }
            }
        }
    }

    expect($privateDependencyViolations)->toBe([]);

    foreach ($groups as $slug => $group) {
        expect($group['title'])->not->toBeEmpty()
            ->and($group['description'])->not->toBeEmpty()
            ->and($root.'/resources/views/studio/demos/'.$slug.'.blade.php')->toBeFile();

        expect($group['components'])->not->toBeEmpty();
    }

    $expectedDemoGroups = array_keys($groups);
    sort($expectedDemoGroups);
    expect($demoGroups)->toBe($expectedDemoGroups);

    foreach ($kinds as $kind) {
        expect($kind['title'])->not->toBeEmpty()
            ->and($kind['singular'])->not->toBeEmpty()
            ->and($kind['description'])->not->toBeEmpty();
    }

    foreach ($navigationGroups as $navigationGroup) {
        expect($navigationGroup['title'])->not->toBeEmpty()
            ->and($navigationGroup['description'])->not->toBeEmpty();
    }

    foreach ($components as $component => $metadata) {
        expect($metadata['title'])->not->toBeEmpty()
            ->and($metadata['description'])->not->toBeEmpty()
            ->and($metadata['kindTitle'])->not->toBeEmpty()
            ->and($metadata['kindDescription'])->not->toBeEmpty()
            ->and($groups)->toHaveKey($metadata['group'])
            ->and($metadata['members'])->toContain($component);
    }
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

it('namespaces package-owned Blade identifiers away from host pages', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $attributeViolations = [];
    $literalIdViolations = [];
    $alpineIdViolations = [];

    foreach (ProjectFiles::bladeFilesIn($views) as $file) {
        $relativePath = ProjectFiles::relativePath($file, $views);
        $contents = file_get_contents($file->getPathname());

        preg_match_all('/(?:^|\s):?data-(?<name>[a-z0-9_-]+)/m', $contents, $attributes);

        foreach (array_unique($attributes['name']) as $name) {
            if (! str_starts_with($name, 'ndb-')) {
                $attributeViolations[] = $relativePath.': data-'.$name;
            }
        }

        preg_match_all('/(?:^|\s)(?:::|:)?id="(?<id>[^"]+)"/m', $contents, $ids);

        foreach (array_unique($ids['id']) as $id) {
            if (! str_contains($id, '{{') && ! str_contains($id, '$id(') && ! str_starts_with($id, 'newdebugbar')) {
                $literalIdViolations[] = $relativePath.': '.$id;
            }
        }

        preg_match_all('/x-id="\[(?<ids>[^]]+)]"/', $contents, $alpineGroups);

        foreach ($alpineGroups['ids'] as $group) {
            preg_match_all("/'(?<id>[^']+)'/", $group, $alpineIds);

            foreach ($alpineIds['id'] as $id) {
                if (! str_starts_with($id, 'newdebugbar')) {
                    $alpineIdViolations[] = $relativePath.': '.$id;
                }
            }
        }
    }

    expect($attributeViolations)->toBe([])
        ->and($literalIdViolations)->toBe([])
        ->and($alpineIdViolations)->toBe([]);
});

it('uses one popover surface for toolbar and inspector menus', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/mobile-toolbar-popover.blade.php',
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
        'livewire/livewire/view-tabs.blade.php',
        'livewire/sections/authorization.blade.php',
        'livewire/sections/events.blade.php',
    ] as $view) {
        $contents = file_get_contents($views.'/'.$view);

        expect($contents)
            ->toContain('<x-newdebugbar::filter-tabs')
            ->toContain('<x-newdebugbar::filter-tab');
    }

    foreach ([
        'components/cache-detail-tabs.blade.php',
        'components/http-client-detail-tabs.blade.php',
        'components/query-detail.blade.php',
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
        ->toContain('<x-newdebugbar::inspector-source-panel')
        ->toContain('<x-newdebugbar::inspector-source-fact');

    expect($controls)
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('<x-newdebugbar::filter-tabs')
        ->not->toContain('Oldest')
        ->not->toContain('Slowest');

    expect($header)
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->toContain('<x-newdebugbar::inspector-operation-badge')
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

});

it('composes the Cache workspace from the shared inspector components', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/cache.blade.php');
    $workspace = file_get_contents($views.'/components/cache-workspace.blade.php');
    $detail = file_get_contents($views.'/components/cache-detail.blade.php');
    $controls = file_get_contents($views.'/components/cache-controls.blade.php');
    $header = file_get_contents($views.'/components/cache-header.blade.php');
    $listItem = file_get_contents($views.'/components/cache-list-item.blade.php');
    $overview = file_get_contents($views.'/components/cache-overview-panel.blade.php');
    $raw = file_get_contents($views.'/components/cache-raw-panel.blade.php');

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
        ->toContain('<x-newdebugbar::inspector-source-panel')
        ->toContain('<x-newdebugbar::inspector-source-fact');

    expect($controls)
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('<x-newdebugbar::filter-tabs')
        ->not->toContain('cacheSort');

    expect($header)
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->toContain('<x-newdebugbar::inspector-operation-badge')
        ->not->toContain('font-mono');

    expect($listItem)
        ->toContain('<x-newdebugbar::inspector-operation-badge')
        ->not->toContain('#{{');

    expect($overview)
        ->toContain('<x-newdebugbar::cache-overview-facts')
        ->toContain('<x-newdebugbar::inspector-definition-list')
        ->not->toContain('What happened')
        ->not->toContain('Check next');

    expect($raw)->toContain('<x-newdebugbar::inspector-evidence');
});

it('composes Models as a shared split inspector with reusable explanations', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/models.blade.php');
    $detail = file_get_contents($views.'/components/model-group-detail.blade.php');
    $explanation = file_get_contents($views.'/components/inspector-explanation.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('data-ndb-model-summary-count')
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::model-group')
        ->toContain('<x-newdebugbar::model-group-detail')
        ->not->toContain('Model activity totals');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('variant="segmented"')
        ->toContain('data-ndb-model-detail-panel="records"')
        ->toContain('data-ndb-model-detail-panel="source"')
        ->not->toContain('data-ndb-model-detail-panel="overview"')
        ->not->toContain('Write evidence')
        ->not->toContain('related quer')
        ->not->toContain('navigateToQueriesAtSource');

    expect(substr_count($detail, '<x-newdebugbar::inspector-explanation'))->toBe(3);

    expect($explanation)
        ->toContain("@props(['title', 'description'])")
        ->toContain('{{ $title }}')
        ->toContain('{{ $description }}')
        ->toContain('ndb:text-xs ndb:font-bold')
        ->toContain('ndb:text-[11px] ndb:leading-5');
});

it('composes Livewire as one shared inspector workspace with focused details', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/livewire.blade.php');
    $controls = file_get_contents($views.'/livewire/livewire/controls.blade.php');
    $activityDetail = file_get_contents($views.'/livewire/livewire/activity-detail.blade.php');
    $componentDetail = file_get_contents($views.'/livewire/livewire/component-detail.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('x-if="livewireTab === \'activity\'"')
        ->toContain('x-if="livewireTab === \'components\'"')
        ->not->toContain('<x-newdebugbar::livewire-split-view');

    expect($controls)
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('livewireActivityOrder')
        ->not->toContain('Newest first')
        ->not->toContain('Oldest first');

    foreach ([$activityDetail, $componentDetail] as $detail) {
        expect($detail)
            ->toContain('<x-newdebugbar::inspector-detail-header')
            ->toContain('<x-newdebugbar::inspector-detail-tabs')
            ->toContain('variant="segmented"')
            ->toContain('<x-newdebugbar::inspector-facts');
    }

    expect($activityDetail)
        ->toContain('data-ndb-livewire-detail-panel="overview"')
        ->toContain('data-ndb-livewire-detail-panel="trace"')
        ->toContain('<x-newdebugbar::inspector-explanation');

    expect($componentDetail)
        ->toContain('data-ndb-livewire-detail-panel="properties"')
        ->toContain('data-ndb-livewire-detail-panel="source"')
        ->toContain('<x-newdebugbar::livewire-property-editor')
        ->toContain('<x-newdebugbar::inspector-source-fact')
        ->toContain('<x-newdebugbar::inspector-evidence');
});

it('composes Queries as a bounded shared list detail workspace', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/components/query-section.blade.php');
    $detail = file_get_contents($views.'/components/query-detail.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->toContain('<x-newdebugbar::query-detail')
        ->not->toContain('<details')
        ->not->toContain('<x-newdebugbar::query-execution')
        ->not->toContain('<x-newdebugbar::query-actions');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-back')
        ->toContain('<x-newdebugbar::inspector-detail-header')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('variant="segmented"')
        ->toContain('<x-newdebugbar::inspector-facts')
        ->toContain('<x-newdebugbar::inspector-evidence')
        ->toContain('<x-newdebugbar::inspector-source-panel')
        ->toContain('<x-newdebugbar::inspector-source-fact')
        ->toContain('<x-newdebugbar::inspector-explanation')
        ->toContain('<x-newdebugbar::inspector-action')
        ->toContain('<template x-if="queryDetailTab === \'query\'">')
        ->toContain('<template x-if="queryDetailTab === \'bindings\'">')
        ->toContain('<template x-if="queryDetailTab === \'source\'">')
        ->toContain('<template x-if="queryDetailTab === \'explain\'">')
        ->not->toContain('<details')
        ->not->toContain('<pre');
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
        'components/cache-detail.blade.php',
        'components/http-client-detail.blade.php',
        'components/mail-message-details.blade.php',
        'components/notification-detail.blade.php',
        'components/query-detail.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-source-panel')
            ->toContain('<x-newdebugbar::inspector-source-fact');
    }

    expect(file_get_contents($views.'/components/mail-message-details.blade.php'))
        ->toContain('<x-newdebugbar::inspector-source-panel')
        ->toContain('data-ndb-mail-detail-panel="source"');

    expect(file_get_contents($views.'/components/inspector-source-panel.blade.php'))
        ->toContain('<x-newdebugbar::inspector-stack')
        ->toContain('data-ndb-inspector-source-panel');

    expect(file_get_contents($resources.'/css/newdebugbar.css'))
        ->toContain('@fontsource-variable/jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2')
        ->toContain('--font-mono: "JetBrains Mono Variable"')
        ->toContain('font-variant-ligatures: contextual common-ligatures discretionary-ligatures');

    expect(file_get_contents($views.'/components/inspector-source-link.blade.php'))
        ->toContain('ndb:p-0')
        ->toContain('ndb:underline')
        ->not->toContain('<x-newdebugbar::icon')
        ->not->toContain('hover:bg-zinc-100');

    expect(file_get_contents($views.'/components/inspector-source-fact.blade.php'))
        ->not->toContain('<x-newdebugbar::icon');
});

it('routes every syntax-highlighted block through one code component', function () {
    $resources = dirname(__DIR__, 2).'/resources';
    $views = $resources.'/views';
    $rawCodeBlocks = [];

    foreach (ProjectFiles::bladeFilesIn($views) as $file) {
        $relativePath = ProjectFiles::relativePath($file, $views);

        if ($relativePath !== 'components/code-block.blade.php' && str_contains(file_get_contents($file->getPathname()), '<pre')) {
            $rawCodeBlocks[] = $relativePath;
        }
    }

    expect($rawCodeBlocks)->toBe([]);
    expect(file_get_contents($views.'/components/code-block.blade.php'))
        ->toContain('data-ndb-language="{{ $language }}"')
        ->toContain('ndb-code');

    expect(file_get_contents($resources.'/js/newdebugbar.js'))
        ->toContain("registerLanguage('http', http)")
        ->toContain("registerLanguage('json', json)")
        ->toContain("registerLanguage('php', php)")
        ->toContain("registerLanguage('sql', sql)");
});

it('uses the top-only frame across edge-to-edge inspector workspaces', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/cache-workspace.blade.php',
        'components/http-client-workspace.blade.php',
        'components/query-section.blade.php',
        'livewire/sections/authorization.blade.php',
        'livewire/sections/events.blade.php',
        'livewire/sections/logs.blade.php',
        'livewire/sections/messages.blade.php',
        'livewire/sections/livewire.blade.php',
        'livewire/sections/models.blade.php',
        'livewire/sections/mail.blade.php',
        'livewire/sections/notifications.blade.php',
        'livewire/sections/overview.blade.php',
        'livewire/sections/validation.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-workspace')
            ->toContain('frame="top"');
    }
});

it('composes Authorization from the shared inspector workspace anatomy', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/authorization.blade.php');
    $detail = file_get_contents($views.'/components/authorization-detail.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('<input')
        ->not->toContain('<select');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-empty');
});

it('composes Events from the shared inspector workspace anatomy', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/events.blade.php');
    $detail = file_get_contents($views.'/components/event-detail.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('<input')
        ->not->toContain('<select')
        ->not->toContain('data-ndb-event-sort');

    expect($detail)
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-empty');
});

it('composes Logs as a shared full-height inspector stream', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $section = file_get_contents($views.'/livewire/sections/logs.blade.php');
    $workspace = file_get_contents($views.'/components/inspector-workspace.blade.php');

    expect($section)
        ->toContain('<x-newdebugbar::inspector-workspace mode="stream" frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('<input')
        ->not->toContain('<select')
        ->not->toContain('data-ndb-log-order');

    expect($workspace)
        ->toContain("'stream' =>")
        ->toContain('data-ndb-inspector-stream-body');
});

it('composes Messages and Validation as shared diagnostic streams', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach (['messages', 'validation'] as $section) {
        $contents = file_get_contents($views.'/livewire/sections/'.$section.'.blade.php');

        expect($contents)
            ->toContain('<x-newdebugbar::inspector-workspace mode="stream" frame="top"')
            ->toContain('<x-newdebugbar::inspector-list-controls');
    }

    expect(file_get_contents($views.'/livewire/sections/validation.blade.php'))
        ->toContain('<x-newdebugbar::validation-entry');

    expect(file_get_contents($views.'/components/validation-entry.blade.php'))
        ->toContain('<x-newdebugbar::inspector-source-link')
        ->toContain('<x-newdebugbar::inspector-explanation')
        ->not->toContain('ndb:font-mono ndb:text-xs ndb:font-semibold ndb:text-indigo');
});

it('composes Overview and fallback data as shared full-height streams', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach (['overview', 'default'] as $section) {
        expect(file_get_contents($views.'/livewire/sections/'.$section.'.blade.php'))
            ->toContain('<x-newdebugbar::inspector-workspace mode="stream" frame="top"');
    }

    expect(file_get_contents($views.'/livewire/sections/overview.blade.php'))
        ->toContain('<x-newdebugbar::overview-runtime-details');

    expect(file_get_contents($views.'/components/overview-runtime-details.blade.php'))
        ->toContain('<x-newdebugbar::filter-tabs')
        ->toContain('<x-newdebugbar::select-field')
        ->not->toContain('ndb:font-mono ndb:text-[11px] ndb:font-medium');
});

it('uses centered segmented controls across inspector detail panels', function () {
    $views = dirname(__DIR__, 2).'/resources/views';

    foreach ([
        'components/authorization-detail.blade.php',
        'components/cache-detail-tabs.blade.php',
        'components/event-detail.blade.php',
        'components/http-client-detail-tabs.blade.php',
        'livewire/livewire/activity-detail.blade.php',
        'livewire/livewire/component-detail.blade.php',
        'components/model-group-detail.blade.php',
        'components/notification-detail.blade.php',
        'components/query-detail.blade.php',
    ] as $view) {
        expect(file_get_contents($views.'/'.$view))
            ->toContain('<x-newdebugbar::inspector-detail-tabs')
            ->toContain('variant="segmented"');
    }

    expect(file_get_contents($views.'/components/inspector-detail-tabs.blade.php'))
        ->toContain("'align' => 'center'")
        ->toContain('variant="segmented"')
        ->toContain('ndb:sm:col-start-2');

    expect(file_get_contents($views.'/livewire/sections/mail.blade.php'))
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('label="Mail detail"')
        ->toContain('align="left"')
        ->toContain('<x-newdebugbar::filter-tabs')
        ->not->toContain('<input')
        ->not->toContain('<select');
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
