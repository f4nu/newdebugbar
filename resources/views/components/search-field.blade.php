@props([
    'label',
    'placeholder' => 'Search',
])

<label class="ndb:relative ndb:block ndb:min-w-0">
    <span class="ndb:sr-only">{{ $label }}</span>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class('ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-3 ndb:pl-8 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70') }}
    />
    <x-newdebugbar::icon
        name="search"
        size="4"
        class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:left-2.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
    />
</label>
