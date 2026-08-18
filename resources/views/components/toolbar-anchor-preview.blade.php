@props(['placement'])

<div
    aria-hidden="true"
    data-ndb-toolbar-anchor="{{ $placement }}"
    :data-active="toolbarDragging && toolbarDragTarget === '{{ $placement }}'"
    :style="{
        width: toolbarDragWidth + 'px',
        height: toolbarDragHeight + 'px',
    }"
    :class="
        toolbarDragging && toolbarDragTarget === '{{ $placement }}'
            ? 'ndb:scale-100 ndb:opacity-100'
            : 'ndb:scale-[0.985] ndb:opacity-0'
    "
    @class([
        'ndb:pointer-events-none ndb:fixed ndb:left-1/2 ndb:-translate-x-1/2 ndb:rounded-[18px] ndb:border ndb:border-indigo-400/50 ndb:bg-indigo-500/10 ndb:shadow-[inset_0_0_0_1px_rgba(99,102,241,0.08),0_12px_40px_-20px_rgba(79,70,229,0.55)] ndb:transition-[opacity,transform] ndb:duration-[180ms] ndb:ease-out',
        'ndb:top-3' => $placement === 'top',
        'ndb:bottom-3' => $placement === 'bottom',
    ])
></div>
