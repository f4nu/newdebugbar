@props(['placement'])

@php
    $isTop = str_starts_with($placement, 'top');
    $isLeft = str_ends_with($placement, '-left');
    $isRight = str_ends_with($placement, '-right');
    $isCorner = $isLeft || $isRight;
@endphp

<div
    aria-hidden="true"
    data-ndb-toolbar-anchor="{{ $placement }}"
    :data-ndb-active="toolbarDragging && toolbarDragTarget === '{{ $placement }}'"
    @if (! $isCorner)
        :style="{
            width: toolbarPreviewWidth('{{ $placement }}') + 'px',
            height: toolbarPreviewHeight('{{ $placement }}') + 'px',
        }"
    @endif
    :class="
        toolbarDragging && toolbarDragTarget === '{{ $placement }}'
            ? 'ndb:scale-100 ndb:opacity-100'
            : 'ndb:scale-[0.985] ndb:opacity-0'
    "
    @class([
        'ndb:pointer-events-none ndb:fixed ndb:transition-[opacity,transform] ndb:duration-[180ms] ndb:ease-out',
        'ndb:size-56 ndb:rounded-full ndb:bg-[radial-gradient(circle,rgba(99,102,241,0.24)_0%,rgba(99,102,241,0.14)_38%,rgba(99,102,241,0.05)_58%,transparent_78%)] ndb:dark:bg-[radial-gradient(circle,rgba(129,140,248,0.3)_0%,rgba(99,102,241,0.17)_40%,rgba(79,70,229,0.06)_60%,transparent_80%)]' => $isCorner,
        'ndb:rounded-[18px] ndb:border ndb:border-indigo-400/50 ndb:bg-indigo-500/10 ndb:shadow-[inset_0_0_0_1px_rgba(99,102,241,0.08),0_12px_40px_-20px_rgba(79,70,229,0.55)]' => ! $isCorner,
        'ndb:top-0' => $isCorner && $isTop,
        'ndb:bottom-0' => $isCorner && ! $isTop,
        'ndb:left-0 ndb:-translate-x-1/2' => $isLeft,
        'ndb:right-0 ndb:translate-x-1/2' => $isRight,
        'ndb:-translate-y-1/2' => $isCorner && $isTop,
        'ndb:translate-y-1/2' => $isCorner && ! $isTop,
        'ndb:top-3' => ! $isCorner && $isTop,
        'ndb:bottom-3' => ! $isCorner && ! $isTop,
        'ndb:left-1/2 ndb:-translate-x-1/2' => ! $isCorner,
    ])
></div>
