@props(['title', 'description'])

<header {{ $attributes->class('ndb:min-w-0') }}>
    <h4 class="ndb:text-xs ndb:font-bold">{{ $title }}</h4>
    <p class="ndb:mt-0.5 ndb:max-w-3xl ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
        {{ $description }}
    </p>
</header>
