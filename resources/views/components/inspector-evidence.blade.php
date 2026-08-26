@props([
    'label' => null,
    'language' => 'json',
])

<section data-ndb-inspector-evidence {{ $attributes }}>
    @if ($label !== null || isset($aside))
        <div class="ndb:mb-2 ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
            @if ($label !== null)
                <h4 class="ndb:text-xs ndb:font-bold">{{ $label }}</h4>
            @endif
            @isset($aside)
                <div class="ndb:shrink-0">{{ $aside }}</div>
            @endisset
        </div>
    @endif
    <x-newdebugbar::code-block
        :language="$language"
        :code-attributes="$value->attributes"
    >{{ $value }}</x-newdebugbar::code-block>
</section>
