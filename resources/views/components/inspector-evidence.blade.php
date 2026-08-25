@props([
    'label' => null,
    'language' => 'json',
])

<section data-ndb-inspector-evidence {{ $attributes }}>
    @if ($label !== null)
        <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">{{ $label }}</h4>
    @endif
    <x-newdebugbar::code-block
        :language="$language"
        :code-attributes="$value->attributes"
    >{{ $value }}</x-newdebugbar::code-block>
</section>
