<?php

use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Support\Redactor;

function historySummaryProfile(string $requestType, array $request = [], array $sections = []): array
{
    return [
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'metrics' => ['duration_ms' => 10, 'peak_memory_mb' => 8],
        'findings' => [],
        'sections' => [
            'request' => [
                'summary' => ['method' => 'POST', 'status' => 200],
                'payload' => [
                    'path' => '/example',
                    'request_type' => $requestType,
                    ...$request,
                ],
            ],
            'queries' => ['summary' => []],
            'cache' => ['summary' => []],
            'exceptions' => ['summary' => ['count' => 0]],
            ...$sections,
        ],
    ];
}

it('summarizes the application event behind Livewire requests', function () {
    $summary = (new ProfileSummaryPresenter(new Redactor))->present(historySummaryProfile('livewire', sections: [
        'livewire' => ['payload' => ['items' => [[
            'phase' => 'response',
            'component' => 'work-order-board',
            'actions' => ['advance'],
            'updated_properties' => [],
            'validation_fields' => [],
        ]]], 'summary' => ['count' => 1]],
    ]));

    expect($summary['activity'])->toBe('Work Order Board → advance()');
});

it('summarizes Inertia partial reloads and redirects', function () {
    $presenter = new ProfileSummaryPresenter(new Redactor);
    $partial = $presenter->present(historySummaryProfile('inertia_partial', [
        'headers' => ['x-inertia-partial-data' => ['stats,workOrders']],
    ]));
    $redirect = $presenter->present(historySummaryProfile('inertia_redirect', [
        'response_headers' => ['location' => ['/work-orders']],
    ]));

    expect($partial['activity'])->toBe('Partial reload: stats,workOrders')
        ->and($redirect['activity'])->toBe('Redirected to /work-orders');
});
