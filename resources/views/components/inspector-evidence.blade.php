@props(['label'])

<section data-ndb-inspector-evidence {{ $attributes }}>
    <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">{{ $label }}</h4>
    <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code {{ $value->attributes }}>{{ $value }}</code></pre>
</section>
