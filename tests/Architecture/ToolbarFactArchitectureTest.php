<?php

it('routes only actionable toolbar facts through the shared toolbar button', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $component = file_get_contents($views.'/components/toolbar-button.blade.php');
    $compact = file_get_contents($views.'/livewire/toolbar.blade.php');
    $expanded = file_get_contents($views.'/livewire/inspector-header.blade.php');

    expect($component)
        ->toContain("'section' => null")
        ->toContain('@if ($section)')
        ->toContain('<button')
        ->toContain('<div');

    foreach ([$compact, $expanded] as $header) {
        preg_match_all('/<x-newdebugbar::toolbar-button(?<attributes>.*?)>/s', $header, $matches);

        $facts = collect($matches['attributes'])
            ->mapWithKeys(function (string $attributes): array {
                preg_match('/data-ndb-(?:toolbar|header-fact)="(?<name>[^"]+)"/', $attributes, $match);

                return [($match['name'] ?? '') => $attributes];
            });

        expect($facts)->toHaveKeys(['environment', 'duration', 'memory', 'queries'])
            ->and($facts['environment'])->not->toContain('section=')
            ->and($facts['memory'])->not->toContain('section=')
            ->and($facts['duration'])->toContain('section="request"')
            ->and($facts['queries'])->toContain('section="queries"');
    }
});

it('keeps Peak visible but non-interactive in the shared mobile metric strip', function () {
    $metrics = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/mobile-request-metrics.blade.php');

    expect($metrics)
        ->toMatch("/'key' => 'memory',\\s*'section' => null,/")
        ->toContain('@if ($metric[\'section\'])')
        ->toContain('<button')
        ->toContain('<div');
});
