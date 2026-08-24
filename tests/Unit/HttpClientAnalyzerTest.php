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
            'request' => [
                'body_size_bytes' => 0,
            ],
            'response' => [
                'body' => ['recommendations' => ['debugging', 'profiling']],
                'body_size_bytes' => 128,
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
                'body_size_bytes' => 52,
            ],
            'exception_message' => 'Remote service failed',
            'response' => [
                'body' => ['message' => 'Service unavailable.'],
                'body_size_bytes' => 34,
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
            'request' => ['body_size_bytes' => 28],
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
        ->request_body_size_label->toBe('0 B')
        ->response_body_size_label->toBe('128 B')
        ->response_summary->toBe('A response body was captured.')
        ->meaning->toBe('The upstream service responded more slowly than expected.')
        ->what_happened->toBe('api.recommendations.test returned HTTP 200 OK in 319.53 ms.')
        ->why_it_matters->toContain('250 ms')
        ->and($analysis['items'][1])
        ->execution->toBe(2)
        ->failed->toBeTrue()
        ->status_label->toBe('503 Service Unavailable')
        ->duration_label->toBe('68.44 ms')
        ->timing_summary->toBe('68.44 ms')
        ->request_body_size_label->toBe('52 B')
        ->response_body_size_label->toBe('34 B')
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
        ->status_label->toBe('Connection failed')
        ->list_status_label->toBe('Failed')
        ->duration_label->toBe('—')
        ->timing_summary->toBe('—')
        ->request_body_size_label->toBe('28 B')
        ->response_body_size_label->toBe('—')
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
        ->host->toBe('—')
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

it('distinguishes redirects and generic HTTP failures from connection failures', function () {
    $items = (new HttpClientAnalyzer)->analyze([
        [
            'method' => 'GET',
            'url' => 'https://api.example.test/v1/legacy',
            'status' => 302,
            'reason' => 'Found',
            'duration_ms' => 12.4,
            'response' => [
                'headers' => ['Location' => ['https://api.example.test/v2/current']],
                'body_size_bytes' => 0,
            ],
        ],
        [
            'method' => 'POST',
            'url' => 'https://api.example.test/v1/teapot',
            'status' => 418,
            'reason' => "I'm a teapot",
            'duration_ms' => 8.2,
            'response' => ['body' => ['message' => 'Use the coffee endpoint.']],
        ],
    ])['items'];

    expect($items[0])
        ->redirect->toBeTrue()
        ->failed->toBeFalse()
        ->redirect_location->toBe('https://api.example.test/v2/current')
        ->response_body_size_label->toBe('0 B')
        ->response_summary->toBe('Redirected to https://api.example.test/v2/current.')
        ->meaning->toBe('The upstream service redirected the request.')
        ->check_next->toContain('Location header')
        ->and($items[1])
        ->failed->toBeTrue()
        ->redirect->toBeFalse()
        ->check_next->toBe('Inspect the response body, then confirm the request method, URL, headers, and payload.')
        ->check_next->not->toContain('DNS');
});
