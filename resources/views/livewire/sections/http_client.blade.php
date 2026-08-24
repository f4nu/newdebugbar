{{-- Normalizes HTTP Client data, then delegates the workspace to focused view components. --}}
@php
    $httpItems = collect($section['payload']['items'] ?? [])
        ->map(function (array $item): array {
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;

            $item['callsite_label'] = $callsite === null
                ? 'Source unavailable'
                : $callsite['file'].':'.$callsite['line'];

            return $item;
        })
        ->values()
        ->all();
    $httpSummary = $section['summary'] ?? [];
@endphp

<div
    data-ndb-http-client
    x-init="
        initializeHttpClient(JSON.parse(atob($el.querySelector('[data-ndb-http-client-payload]').textContent.trim())))
    "
    class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col"
>
    <script type="application/json" data-ndb-http-client-payload>
        {{ base64_encode(json_encode($httpItems, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)) }}
    </script>

    @if ($httpItems !== [])
        <x-newdebugbar::http-client-workspace :items="$httpItems" :summary="$httpSummary" />
    @else
        <x-newdebugbar::http-client-empty />
    @endif
</div>
