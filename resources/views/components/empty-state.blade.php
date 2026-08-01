@props(['label', 'success' => false])

<div class="ndb:rounded-2xl ndb:border ndb:border-dashed ndb:border-zinc-300 ndb:px-6 ndb:py-10 ndb:text-center ndb:dark:border-zinc-700">
    <span @class([
        'ndb:mx-auto ndb:grid ndb:size-10 ndb:place-items-center ndb:rounded-xl',
        'ndb:bg-emerald-50 ndb:text-emerald-600 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300' => $success,
        'ndb:bg-zinc-100 ndb:text-zinc-400 ndb:dark:bg-zinc-900' => ! $success,
    ])>
        <x-new-debug-bar::icon :name="$success ? 'check' : 'sparkles'" class="ndb:size-4" />
    </span>
    <p class="ndb:mt-3 ndb:text-sm ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $label }}</p>
</div>
