@props([
    'label',
    'span' => false,
])

<label @class(['ndb:relative ndb:block', 'ndb:col-span-2' => $span])>
    <span class="ndb:sr-only">{{ $label }}</span>
    <select {{ $attributes->class('ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70') }}>
        {{ $slot }}
    </select>
    <x-newdebugbar::icon
        name="chevron-down"
        size="3.5"
        class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
    />
</label>
