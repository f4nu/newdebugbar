@php
    $title = \Illuminate\Support\Str::headline($component);
    $description = $components[$component];
@endphp

<section
    id="newdebugbar-studio-{{ $component }}"
    data-ndb-studio-demo="{{ $component }}"
    class="ndb:scroll-mt-24 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
>
    <header class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800">
        <h2 class="ndb:text-sm ndb:font-bold">{{ $title }}</h2>
        <p class="ndb:mt-0.5 ndb:max-w-3xl ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
            {{ $description }}
        </p>
        <code class="ndb:mt-1 ndb:block ndb:text-[11px] ndb:text-zinc-400">&lt;x-newdebugbar::{{ $component }}&gt;</code>
    </header>

    <div data-ndb-studio-demo-surface class="ndb:min-w-0 ndb:bg-zinc-50/70 ndb:p-4 ndb:dark:bg-zinc-900/35">
        {{ $slot }}
    </div>
</section>
