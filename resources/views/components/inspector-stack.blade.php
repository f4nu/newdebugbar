@props([
    'frames',
    'emptyLabel' => 'No application stack was captured.',
])

<template x-if="({{ $frames }}).length === 0">
    <p class="ndb:mt-5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $emptyLabel }}</p>
</template>
<div {{ $attributes->class('ndb:mt-5 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800') }}>
    <template x-for="(frame, index) in {{ $frames }}" :key="frame.file + ':' + frame.line + ':' + index">
        <div class="ndb:py-3 ndb:first:pt-0">
            <code
                class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                x-text="frame.file + ':' + frame.line"
            ></code>
            <p
                class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                x-text="frame.function || 'Application call'"
            ></p>
        </div>
    </template>
</div>
