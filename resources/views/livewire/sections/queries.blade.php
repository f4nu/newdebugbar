{{-- Renders captured database queries. --}}
<x-newdebugbar::query-section
    :section="$section"
    :query-explains="$queryExplains"
    :query-explain-errors="$queryExplainErrors"
/>
