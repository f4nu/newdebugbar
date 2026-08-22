<?php

namespace NewDebugBar\Analysis;

/** Turns captured outbound HTTP requests into actionable, copyable evidence. */
final class HttpClientAnalyzer
{
    public function __construct(private readonly float $slowRequestMs = 250) {}

    public function slowThreshold(): float
    {
        return $this->slowRequestMs;
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     * @return array{summary: array<string, int|float>, items: list<array<string, mixed>>}
     */
    public function analyze(array $requests): array
    {
        $items = [];

        foreach (array_values($requests) as $index => $request) {
            $url = (string) ($request['url'] ?? '');
            $host = (string) (parse_url($url, PHP_URL_HOST) ?: 'Unknown host');
            $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
            $query = parse_url($url, PHP_URL_QUERY);
            $duration = is_numeric($request['duration_ms'] ?? null)
                ? round(max(0, (float) $request['duration_ms']), 2)
                : null;
            $status = is_numeric($request['status'] ?? null) ? (int) $request['status'] : null;
            $failed = (bool) ($request['failed'] ?? false) || ($status !== null && $status >= 400);
            $slow = $duration !== null && $duration >= $this->slowRequestMs;
            $reason = trim((string) ($request['reason'] ?? ''));
            $method = strtoupper((string) ($request['method'] ?? 'HTTP'));

            $items[] = [
                ...$request,
                'execution' => $index + 1,
                'method' => $method,
                'host' => $host,
                'path' => $path,
                'query' => is_string($query) && $query !== '' ? $query : null,
                'duration_ms' => $duration,
                'status' => $status,
                'failed' => $failed,
                'slow' => $slow,
                'attention' => $failed || $slow,
                'status_label' => $this->statusLabel($status, $reason, $failed),
                'meaning' => $this->meaning($status, $failed, $slow),
                'what_happened' => $this->whatHappened($host, $status, $reason, $failed, $slow, $duration),
                'why_it_matters' => $this->whyItMatters($status, $failed, $slow),
                'check_next' => $this->checkNext($status, $failed, $slow),
                'curl' => $this->curl($method, $url, $request['request'] ?? null),
                'search' => mb_strtolower(implode(' ', array_filter([
                    $method,
                    $url,
                    $host,
                    $path,
                    $status === null ? null : (string) $status,
                    $reason,
                    $request['exception_class'] ?? null,
                    $request['exception_message'] ?? null,
                    $request['callsite']['file'] ?? null,
                ], fn (mixed $value): bool => is_scalar($value) && (string) $value !== ''))),
            ];
        }

        return [
            'summary' => [
                'retained_count' => count($items),
                'failed_count' => count(array_filter($items, fn (array $item): bool => $item['failed'])),
                'slow_count' => count(array_filter($items, fn (array $item): bool => $item['slow'])),
                'attention_count' => count(array_filter($items, fn (array $item): bool => $item['attention'])),
                'slow_threshold_ms' => $this->slowRequestMs,
            ],
            'items' => $items,
        ];
    }

    private function statusLabel(?int $status, string $reason, bool $failed): string
    {
        if ($status !== null) {
            return trim($status.' '.$reason);
        }

        return $failed ? 'Connection error' : 'No response';
    }

    private function whatHappened(
        string $host,
        ?int $status,
        string $reason,
        bool $failed,
        bool $slow,
        ?float $duration,
    ): string {
        if ($status !== null) {
            $statusLabel = trim($status.' '.$reason);

            return sprintf('%s returned HTTP %s.', $host, $statusLabel);
        }

        if ($failed) {
            return sprintf('The request to %s failed before a response arrived.', $host);
        }

        if ($slow) {
            return sprintf('%s responded in %s ms.', $host, $this->number($duration));
        }

        return sprintf('The request to %s completed.', $host);
    }

    private function meaning(?int $status, bool $failed, bool $slow): string
    {
        if ($status !== null && $status >= 500) {
            return 'The upstream service could not complete this request.';
        }

        if ($status !== null && $status >= 400) {
            return 'The upstream service rejected this request.';
        }

        if ($failed) {
            return 'No response reached the application.';
        }

        if ($slow) {
            return 'The upstream service responded more slowly than expected.';
        }

        return 'The upstream service completed this request.';
    }

    private function whyItMatters(?int $status, bool $failed, bool $slow): string
    {
        if ($failed || ($status !== null && $status >= 400)) {
            return 'This request did not complete normally.';
        }

        if ($slow) {
            return sprintf('This request exceeded the %s ms slow-request threshold.', $this->number($this->slowRequestMs));
        }

        return 'The upstream service completed this request normally.';
    }

    private function checkNext(?int $status, bool $failed, bool $slow): string
    {
        if ($status === 401 || $status === 403) {
            return 'Check the credentials, scopes, and access rules used for this request.';
        }

        if ($status === 404) {
            return 'Check the endpoint path, HTTP method, and remote resource identifier.';
        }

        if ($status === 422) {
            return 'Inspect the response body and compare the submitted payload with the remote validation rules.';
        }

        if ($status === 429) {
            return 'Inspect rate-limit headers, retry timing, and backoff behavior.';
        }

        if ($status !== null && $status >= 500) {
            return 'Confirm endpoint health, timeout, and retry behavior.';
        }

        if ($failed) {
            return 'Check DNS, network access, the endpoint, and timeout settings.';
        }

        if ($slow) {
            return 'Inspect the response size, endpoint work, timeout, and whether this call can leave the request path.';
        }

        return 'No follow-up is needed.';
    }

    private function curl(string $method, string $url, mixed $request): string
    {
        $evidence = is_array($request) ? $request : [];
        $parts = [
            'curl',
            '--request '.$this->shellArgument($method),
            $this->shellArgument($url),
        ];

        foreach ((array) ($evidence['headers'] ?? []) as $name => $values) {
            if (in_array(strtolower((string) $name), ['content-length', 'host'], true)) {
                continue;
            }

            foreach ((array) $values as $value) {
                $parts[] = '--header '.$this->shellArgument($name.': '.$value);
            }
        }

        $body = $evidence['body'] ?? null;

        if ($body !== null && $body !== '') {
            $encoded = is_string($body)
                ? $body
                : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (is_string($encoded) && $encoded !== '') {
                $parts[] = '--data-raw '.$this->shellArgument($encoded);
            }
        }

        return implode(" \\\n  ", $parts);
    }

    private function shellArgument(string $value): string
    {
        return "'".str_replace("'", "'\"'\"'", $value)."'";
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
