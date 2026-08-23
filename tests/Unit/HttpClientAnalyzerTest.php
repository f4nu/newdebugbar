<?php

use NewDebugBar\Analysis\HttpClientAnalyzer;

it('turns outbound requests into focused failure and timing evidence', function () {
    $analysis = (new HttpClientAnalyzer(slowRequestMs: 250))->analyze([
        [
            'method' => 'get',
            'url' => 'https://api.recommendations.test/v2/personalized/homepage?locale=en',
            'status' => 200,
            'reason' => 'OK',
            'duration_ms' => 319.53,
            'failed' => false,
            'response' => [
                'body' => ['recommendations' => ['debugging', 'profiling']],
            ],
            'callsite' => ['file' => 'routes/web.php', 'line' => 122],
        ],
        [
            'method' => 'delete',
            'url' => 'https://api.error.test/v1/stale-cache/item',
            'status' => 503,
            'reason' => 'Service Unavailable',
            'duration_ms' => 68.44,
            'failed' => true,
            'request' => [
                'headers' => [
                    'Authorization' => '[redacted]',
                    'Content-Type' => ['application/json'],
                    'Host' => ['api.error.test'],
                ],
                'body' => ['name' => "D'Angelo", 'token' => '[redacted]'],
            ],
            'exception_message' => 'Remote service failed',
            'response' => [
                'body' => ['message' => 'Service unavailable.'],
            ],
            'callsite' => ['file' => 'routes/web.php', 'line' => 125],
        ],
        [
            'method' => 'post',
            'url' => 'https://api.down.test/v1/webhooks/deliver',
            'status' => null,
            'duration_ms' => null,
            'failed' => true,
            'exception_class' => 'Illuminate\\Http\\Client\\ConnectionException',
        ],
    ]);

    expect($analysis['summary'])->toBe([
        'retained_count' => 3,
        'failed_count' => 2,
        'slow_count' => 1,
        'attention_count' => 3,
        'slow_threshold_ms' => 250.0,
    ])->and($analysis['items'][0])
        ->execution->toBe(1)
        ->host->toBe('api.recommendations.test')
        ->path->toBe('/v2/personalized/homepage')
        ->query->toBe('locale=en')
        ->slow->toBeTrue()
        ->attention->toBeTrue()
        ->status_label->toBe('200 OK')
        ->duration_label->toBe('319.53 ms')
        ->timing_summary->toBe('319.53 ms, above the 250 ms threshold')
        ->response_summary->toBe('A response body was captured.')
        ->meaning->toBe('The upstream service responded more slowly than expected.')
        ->why_it_matters->toContain('250 ms')
        ->and($analysis['items'][1])
        ->execution->toBe(2)
        ->failed->toBeTrue()
        ->status_label->toBe('503 Service Unavailable')
        ->duration_label->toBe('68.44 ms')
        ->timing_summary->toBe('68.44 ms')
        ->response_summary->toBe('Remote service failed')
        ->meaning->toBe('The upstream service could not complete this request.')
        ->what_happened->toBe('api.error.test returned HTTP 503 Service Unavailable.')
        ->check_next->toBe('Confirm endpoint health, timeout, and retry behavior.')
        ->curl->toContain(
            "--request 'DELETE'",
            "'https://api.error.test/v1/stale-cache/item'",
            "--header 'Authorization: [redacted]'",
            "--header 'Content-Type: application/json'",
            "--data-raw '{\"name\":\"D'\"'\"'Angelo\",\"token\":\"[redacted]\"}'",
        )
        ->curl->not->toContain("--header 'Host:")
        ->and($analysis['items'][2])
        ->duration_ms->toBeNull()
        ->status_label->toBe('Connection error')
        ->duration_label->toBe('Timing unavailable')
        ->timing_summary->toBe('Timing unavailable')
        ->response_summary->toBe('No response was captured.')
        ->meaning->toBe('No response reached the application.')
        ->check_next->toBe('Check DNS, network access, the endpoint, and timeout settings.')
        ->search->toContain('connectionexception');
});

it('keeps successful fast requests quiet', function () {
    $item = (new HttpClientAnalyzer)->analyze([[
        'method' => 'GET',
        'url' => 'not-a-url',
        'status' => 204,
        'duration_ms' => 0,
        'response' => ['body' => null],
    ]])['items'][0];

    expect($item)
        ->host->toBe('Unknown host')
        ->path->toBe('not-a-url')
        ->failed->toBeFalse()
        ->slow->toBeFalse()
        ->attention->toBeFalse()
        ->duration_label->toBe('<0.01 ms')
        ->timing_summary->toBe('<0.01 ms')
        ->response_summary->toBe('No response body was returned.')
        ->meaning->toBe('The upstream service completed this request.')
        ->check_next->toBe('No follow-up is needed.');
});
