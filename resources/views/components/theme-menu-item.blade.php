<button
    type="button"
    role="menuitem"
    @click="
        toggleTheme();
        closeMobileToolbarMenu();
    "
    {{ $attributes->class('ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10') }}
>
    <span
        x-show.important="resolvedTheme !== 'dark'"
        class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
        ><x-newdebugbar::icon name="moon" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400"
    /></span>
    <span
        x-show.important="resolvedTheme === 'dark'"
        class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
        ><x-newdebugbar::icon name="sun" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400"
    /></span>
    <span
        class="ndb:text-sm ndb:font-medium"
        x-text="resolvedTheme === 'dark' ? 'Use light theme' : 'Use dark theme'"
    ></span>
</button>
