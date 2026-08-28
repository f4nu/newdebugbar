@props([
    'code' => false,
    'label' => null,
])

<div
    data-ndb-inspector-source-fact
    {{
        $attributes->class(
            'ndb:min-w-0 ndb:rounded-lg ndb:border ndb:border-zinc-200/80 ndb:bg-zinc-50/70 ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/55',
        )
    }}
>
    <div class="ndb:min-w-0 ndb:flex-1">
        @isset($term)
            <dt {{ $term->attributes->class('ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400') }}>
                {{ $term }}
            </dt>
        @else
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $label }}</dt>
        @endisset
        <dd class="ndb:mt-1 ndb:min-w-0">
            @isset($value)
                @if ($code)
                    <code
                        {{
                            $value->attributes->class(
                                'ndb:block ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-medium ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200',
                            )
                        }}
                    >{{ $value }}</code>
                @else
                    <span
                        {{
                            $value->attributes->class(
                                'ndb:block ndb:min-w-0 ndb:break-all ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200',
                            )
                        }}
                    >{{ $value }}</span>
                @endif
            @else
                @if ($code)
                    <code class="ndb:block ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-medium ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200">{{ $slot }}</code>
                @else
                    <span class="ndb:block ndb:min-w-0 ndb:break-all ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200">{{ $slot }}</span>
                @endif
            @endisset
        </dd>
    </div>
</div>
