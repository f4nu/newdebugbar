@props(['placement'])

@php
    $isTop = str_starts_with($placement, 'top');
    $isLeft = str_ends_with($placement, '-left');
    $isRight = str_ends_with($placement, '-right');
@endphp

<div
    aria-hidden="true"
    data-ndb-toolbar-anchor="{{ $placement }}"
    :data-ndb-active="toolbarDragging && toolbarDragTarget === '{{ $placement }}'"
    :style="{
        width: toolbarPreviewWidth('{{ $placement }}') + 'px',
        height: toolbarPreviewHeight('{{ $placement }}') + 'px',
    }"
    :class="
        toolbarDragging && toolbarDragTarget === '{{ $placement }}'
            ? 'ndb:scale-100 ndb:opacity-100'
            : 'ndb:scale-[0.985] ndb:opacity-0'
    "
    @class([
        'ndb:pointer-events-none ndb:fixed ndb:rounded-[18px] ndb:border ndb:border-indigo-400/50 ndb:bg-indigo-500/10 ndb:shadow-[inset_0_0_0_1px_rgba(99,102,241,0.08),0_12px_40px_-20px_rgba(79,70,229,0.55)] ndb:transition-[opacity,transform] ndb:duration-[180ms] ndb:ease-out',
        'ndb:top-3' => $isTop,
        'ndb:bottom-3' => ! $isTop,
        'ndb:left-3' => $isLeft,
        'ndb:right-3' => $isRight,
        'ndb:left-1/2 ndb:-translate-x-1/2' => ! $isLeft && ! $isRight,
    ])
></div>
