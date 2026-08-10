<?php

use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Support\Redactor;

function historySummaryProfile(string $requestType, array $request = [], array $sections = []): array
{
    return [
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'environment' => 'testing',
        'metrics' => ['duration_ms' => 10, 'peak_memory_mb' => 8],
        'findings' => [],
        'sections' => [
            'request' => [
                'summary' => ['method' => 'POST', 'status' => 200],
                'payload' => [
                    'path' => '/example',
                    'request_type' => $requestType,
                    'response_size_bytes' => 13_752,
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

it('summarizes status families and response sizes for the request header', function (int $status, string $meaning) {
    $profile = historySummaryProfile('full_page', [
        'response_size_bytes' => 2_621_440,
    ]);
    $profile['sections']['request']['summary']['status'] = $status;

    $summary = (new ProfileSummaryPresenter(new Redactor))->present($profile);

    expect($summary['status_meaning'])->toBe($meaning)
        ->and($summary['response_size'])->toBe('2.50 MB');
})->with([
    'informational' => [101, 'Informational'],
    'success' => [204, 'Success'],
    'redirect' => [302, 'Redirect'],
    'client error' => [404, 'Client error'],
    'server error' => [503, 'Server error'],
]);

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
