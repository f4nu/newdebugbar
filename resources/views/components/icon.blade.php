@props(['name'])

<svg {{ $attributes->class('ndb:size-5 ndb:shrink-0') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />
            @break
        @case('expand')
            <path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('theme')
            <path d="M12 3a9 9 0 1 0 9 9c0-.6-.1-1.1-.2-1.7A6.5 6.5 0 0 1 12 3Z" />
            @break
        @case('pin')
            <path d="m15 4 5 5-3 1-4 4-1 5-2-2-3-3-2-2 5-1 4-4 1-3Z" />
            @break
        @case('chevron-up')
            <path d="m6 15 6-6 6 6" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('warning')
            <path d="M10.3 4.1 2.5 18a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 4.1a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('memory')
            <rect x="5" y="5" width="14" height="14" rx="2" /><path d="M9 9h6v6H9zM9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3" />
            @break
        @case('database')
            <ellipse cx="12" cy="5" rx="8" ry="3" /><path d="M4 5v7c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12v7c0 1.7 3.6 3 8 3s8-1.3 8-3v-7" />
            @break
        @case('code')
            <path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14" />
            @break
        @case('copy')
            <rect x="8" y="8" width="11" height="11" rx="2" /><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
            @break
        @default
            <rect x="4" y="4" width="16" height="16" rx="4" /><path d="M8 9h8M8 13h8M8 17h5" />
    @endswitch
</svg>
