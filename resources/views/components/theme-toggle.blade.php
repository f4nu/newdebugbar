@props([
    'scope',
    'direction' => 'dynamic',
    'darkSurface' => false,
])

@php
    $themes = [
        'system' => ['System', 'monitor'],
        'light' => ['Light', 'sun'],
        'dark' => ['Dark', 'moon'],
    ];
@endphp

<div
    data-ndb-theme-control="{{ $scope }}"
    @click.outside="if (themeMenuScope === @js($scope)) closeThemeMenu(false)"
    class="ndb:relative ndb:flex ndb:shrink-0"
>
    <x-newdebugbar::icon-button
        :dark-surface="$darkSurface"
        data-ndb-theme-trigger="{{ $scope }}"
        @click="toggleThemeMenu('{{ $scope }}', $el)"
        ::aria-expanded="themeMenuScope === '{{ $scope }}'"
        ::aria-label="`Color theme: ${theme === 'system' ? 'System' : theme === 'light' ? 'Light' : 'Dark'}`"
        ::title="`Color theme: ${theme === 'system' ? 'System' : theme === 'light' ? 'Light' : 'Dark'}`"
        aria-haspopup="menu"
        aria-controls="newdebugbar-theme-menu-{{ $scope }}"
        {{ $attributes->class('ndb:size-9 ndb:rounded-xl') }}
    >
        @foreach ($themes as $theme => [$label, $icon])
            <span
                x-cloak
                x-show.important="theme === @js($theme)"
                class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
            >
                <x-newdebugbar::icon :name="$icon" class="ndb:size-4" />
            </span>
        @endforeach
    </x-newdebugbar::icon-button>

    <x-newdebugbar::popover-surface
        :direction="$direction"
        align="right"
        width-class="ndb:w-44"
        surface-class="ndb:p-1.5"
        x-cloak
        x-show.important="themeMenuScope === '{{ $scope }}'"
        x-transition:enter="ndb:transition ndb:duration-100 ndb:ease-out"
        x-transition:enter-start="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
        x-transition:enter-end="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
        x-transition:leave="ndb:transition ndb:duration-75 ndb:ease-in"
        x-transition:leave-start="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
        x-transition:leave-end="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
    >
        <div
            id="newdebugbar-theme-menu-{{ $scope }}"
            data-ndb-theme-menu="{{ $scope }}"
            role="menu"
            aria-label="Color theme"
            @keydown.down.prevent="moveThemeMenu(1, $el)"
            @keydown.up.prevent="moveThemeMenu(-1, $el)"
            @keydown.home.prevent="$el.querySelector('[data-ndb-theme-option]')?.focus()"
            @keydown.end.prevent="$el.querySelector('[data-ndb-theme-option]:last-child')?.focus()"
        >
            @foreach ($themes as $theme => [$label, $icon])
                <button
                    type="button"
                    role="menuitemradio"
                    data-ndb-theme-option="{{ $theme }}"
                    @click="setTheme(@js($theme)); closeThemeMenu()"
                    :aria-checked="theme === @js($theme)"
                    :class="theme === @js($theme) ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300' : 'ndb:text-zinc-700 ndb:hover:bg-zinc-100 ndb:dark:text-zinc-200 ndb:dark:hover:bg-white/10'"
                    class="ndb:flex ndb:min-h-10 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                >
                    <x-newdebugbar::icon
                        :name="$icon"
                        class="ndb:size-4 ndb:shrink-0 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    />
                    <span class="ndb:min-w-0 ndb:flex-1 ndb:text-sm ndb:font-medium">{{ $label }}</span>
                    <span
                        x-cloak
                        x-show.important="theme === @js($theme)"
                        class="ndb:flex ndb:size-4 ndb:shrink-0 ndb:items-center ndb:justify-center"
                    >
                        <x-newdebugbar::icon name="check" class="ndb:size-4" />
                    </span>
                </button>
            @endforeach
        </div>
    </x-newdebugbar::popover-surface>
</div>
