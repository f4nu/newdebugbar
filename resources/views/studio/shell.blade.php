<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Studio — New Debug Bar</title>
    <link rel="stylesheet" href="{{ $stylesheetUrl }}" />
</head>
<body class="ndb:m-0 ndb:min-h-screen ndb:bg-zinc-100 ndb:dark:bg-zinc-950">
    <div
        id="newdebugbar"
        data-ndb-studio
        data-ndb-theme="{{ $theme }}"
        class="ndb:min-h-screen ndb:bg-zinc-100 ndb:text-zinc-950 ndb:dark:bg-zinc-950 ndb:dark:text-white"
    >
        <header class="ndb:border-b ndb:border-zinc-200 ndb:bg-white ndb:px-5 ndb:py-5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950 ndb:sm:px-8">
            <div class="ndb:mx-auto ndb:flex ndb:max-w-[96rem] ndb:flex-wrap ndb:items-end ndb:justify-between ndb:gap-4">
                <div class="ndb:min-w-0">
                    <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-[0.16em] ndb:text-indigo-600 ndb:dark:text-indigo-300">
                        New Debug Bar
                    </p>
                    <h1 class="ndb:mt-1 ndb:text-2xl ndb:font-bold ndb:tracking-tight">Studio</h1>
                    <p class="ndb:mt-1 ndb:max-w-2xl ndb:text-sm ndb:leading-6 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        A living catalog of the real components used across the inspector. Review the design system,
                        compare themes, and resize every example against real responsive breakpoints.
                    </p>
                </div>

                <p class="ndb:flex ndb:items-center ndb:gap-3 ndb:text-xs ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    <span>{{ count($groups) }} families</span>
                    <span>{{ collect($groups)->sum(fn (array $group): int => count($group['components'])) }} components</span>
                </p>
            </div>
        </header>

        <div class="ndb:mx-auto ndb:grid ndb:w-full ndb:min-w-0 ndb:max-w-[96rem] ndb:grid-cols-[minmax(0,1fr)] ndb:lg:min-h-[calc(100vh-8.75rem)] ndb:lg:grid-cols-[19rem_minmax(0,1fr)]">
            <aside class="ndb:min-w-0 ndb:border-b ndb:border-zinc-200 ndb:bg-white ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950 ndb:lg:border-r ndb:lg:border-b-0">
                <nav
                    aria-label="Component families"
                    class="ndb:flex ndb:gap-2 ndb:overflow-x-auto ndb:pb-1 ndb:lg:block ndb:lg:space-y-1 ndb:lg:overflow-visible ndb:lg:pb-0"
                >
                    @foreach ($groups as $slug => $group)
                        <a
                            href="{{ route('newdebugbar.studio', ['group' => $slug, 'theme' => $theme, 'width' => $previewWidth]) }}"
                            data-ndb-studio-family="{{ $slug }}"
                            @class([
                                'ndb:block ndb:min-w-44 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:no-underline ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-w-0',
                                'ndb:bg-indigo-50 ndb:text-indigo-950 ndb:dark:bg-indigo-950/55 ndb:dark:text-indigo-100' => $slug === $selected,
                                'ndb:text-zinc-700 ndb:hover:bg-zinc-100 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-900' => $slug !== $selected,
                            ])
                            @if ($slug === $selected) aria-current="page" @endif
                        >
                            <span class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                                <span class="ndb:text-xs ndb:font-bold">{{ $group['title'] }}</span>
                                <span class="ndb:font-mono ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-400">
                                    {{ count($group['components']) }}
                                </span>
                            </span>
                            <span class="ndb:mt-0.5 ndb:hidden ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:lg:block">
                                {{ $group['description'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>

                <details
                    class="ndb:mt-3 ndb:hidden ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800 ndb:lg:block"
                    open
                >
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                        {{ $selectedGroup['title'] }} components
                        <x-newdebugbar::icon
                            name="chevron-down"
                            size="3.5"
                            class="ndb-details-chevron ndb:text-zinc-400 ndb:transition-transform"
                        />
                    </summary>
                    <ul class="ndb:m-0 ndb:grid ndb:list-none ndb:gap-1 ndb:px-3 ndb:pb-3">
                        @foreach ($selectedGroup['components'] as $component => $description)
                            <li data-ndb-studio-component="{{ $component }}" class="ndb:min-w-0">
                                <a
                                    href="#newdebugbar-studio-{{ $component }}"
                                    data-ndb-studio-component-link="{{ $component }}"
                                    class="ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-500 ndb:underline ndb:decoration-zinc-300 ndb:underline-offset-2 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:decoration-zinc-700 ndb:dark:hover:text-white"
                                >{{ $component }}</a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            </aside>

            <main class="ndb:min-w-0 ndb:p-4 ndb:sm:p-6">
                <div class="ndb:flex ndb:flex-wrap ndb:items-end ndb:justify-between ndb:gap-4">
                    <div class="ndb:min-w-0">
                        <h2 class="ndb:text-lg ndb:font-bold">{{ $selectedGroup['title'] }}</h2>
                        <p class="ndb:mt-0.5 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            {{ $selectedGroup['description'] }}
                        </p>
                    </div>

                    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3">
                        <x-newdebugbar::filter-tabs label="Preview theme" variant="segmented">
                            <x-newdebugbar::filter-tab
                                variant="segmented"
                                data-ndb-studio-theme="light"
                                :aria-pressed="$theme === 'light' ? 'true' : 'false'"
                            >Light</x-newdebugbar::filter-tab>
                            <x-newdebugbar::filter-tab
                                variant="segmented"
                                data-ndb-studio-theme="dark"
                                :aria-pressed="$theme === 'dark' ? 'true' : 'false'"
                            >Dark</x-newdebugbar::filter-tab>
                        </x-newdebugbar::filter-tabs>

                        <x-newdebugbar::filter-tabs label="Preview width" variant="segmented">
                            <x-newdebugbar::filter-tab
                                variant="segmented"
                                data-ndb-studio-width="1024"
                                :aria-pressed="$previewWidth === 1024 ? 'true' : 'false'"
                            >
                                Desktop
                            </x-newdebugbar::filter-tab>
                            <x-newdebugbar::filter-tab
                                variant="segmented"
                                data-ndb-studio-width="390"
                                :aria-pressed="$previewWidth === 390 ? 'true' : 'false'"
                            >
                                Mobile
                            </x-newdebugbar::filter-tab>
                        </x-newdebugbar::filter-tabs>
                    </div>
                </div>

                <div class="ndb:mt-4 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-200/60 ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-black/30 ndb:sm:p-5">
                    <div class="ndb:mb-3 ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        <span>Drag the lower-right edge or use a preset.</span>
                        <output data-ndb-studio-width-output class="ndb:font-mono ndb:tabular-nums"
                            >{{ $previewWidth }} px</output>
                    </div>

                    <div data-ndb-studio-canvas class="ndb:overflow-x-auto ndb:pb-3">
                        <div
                            data-ndb-studio-frame-shell
                            class="ndb:mx-auto ndb:h-[42rem] ndb:min-w-[322px] ndb:resize-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-300 ndb:bg-white ndb:shadow-xl ndb:dark:border-zinc-700 ndb:dark:bg-zinc-950"
                            style="width: {{ $previewWidth + 2 }}px"
                        >
                            <iframe
                                data-ndb-studio-frame
                                title="{{ $selectedGroup['title'] }} component preview"
                                src="{{ route('newdebugbar.studio', ['preview' => $selected, 'theme' => $theme]) }}"
                                class="ndb:block ndb:size-full ndb:border-0"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-ndb-studio]');
            const frame = root?.querySelector('[data-ndb-studio-frame]');
            const frameShell = root?.querySelector('[data-ndb-studio-frame-shell]');
            const widthOutput = root?.querySelector('[data-ndb-studio-width-output]');

            if (!root || !frame || !frameShell || !widthOutput) return;

            const setPressed = (selector, dataKey, value) => {
                root.querySelectorAll(selector).forEach((control) => {
                    control.setAttribute('aria-pressed', String(control.dataset[dataKey] === value));
                });
            };

            const persistState = (key, value) => {
                const pageUrl = new URL(window.location.href);
                pageUrl.searchParams.set(key, value);
                window.history.replaceState({}, '', pageUrl);

                root.querySelectorAll('[data-ndb-studio-family]').forEach((link) => {
                    const url = new URL(link.href);
                    url.searchParams.set(key, value);
                    link.href = url.toString();
                });
            };

            const setWidth = (width) => {
                const bounded = Math.max(320, Math.min(1440, Number(width) || 1024));
                frameShell.style.width = `${bounded + 2}px`;
                widthOutput.value = `${Math.round(frame.getBoundingClientRect().width)} px`;
                setPressed('[data-ndb-studio-width]', 'ndbStudioWidth', String(bounded));
                persistState('width', String(bounded));
            };

            root.querySelectorAll('[data-ndb-studio-width]').forEach((control) => {
                control.addEventListener('click', () => setWidth(control.dataset.ndbStudioWidth));
            });

            root.querySelectorAll('[data-ndb-studio-theme]').forEach((control) => {
                control.addEventListener('click', () => {
                    const theme = control.dataset.ndbStudioTheme;
                    const url = new URL(frame.src);
                    url.searchParams.set('theme', theme);
                    frame.src = url.toString();
                    root.dataset.ndbTheme = theme;
                    setPressed('[data-ndb-studio-theme]', 'ndbStudioTheme', theme);
                    persistState('theme', theme);
                });
            });

            root.querySelectorAll('[data-ndb-studio-component-link]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    frame.contentWindow?.postMessage(
                        {
                            type: 'newdebugbar:studio-scroll',
                            component: link.dataset.ndbStudioComponentLink,
                        },
                        window.location.origin,
                    );
                });
            });

            if (typeof ResizeObserver === 'function') {
                new ResizeObserver(() => {
                    const width = Math.round(frame.getBoundingClientRect().width);
                    widthOutput.value = `${width} px`;
                    setPressed('[data-ndb-studio-width]', 'ndbStudioWidth', String(width));
                }).observe(frameShell);
            }
        })();
    </script>
</body>
</html>
