<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $selectedComponent['title'] }} — New Debug Bar Studio</title>
    <link rel="stylesheet" href="{{ $stylesheetUrl }}" />
</head>
<body class="ndb:m-0 ndb:min-h-screen ndb:bg-zinc-100 ndb:dark:bg-zinc-950">
    <div
        id="newdebugbar"
        data-ndb-studio
        data-ndb-theme="{{ $theme }}"
        class="ndb:grid ndb:min-h-screen ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)] ndb:bg-zinc-100 ndb:text-zinc-950 ndb:dark:bg-zinc-950 ndb:dark:text-white ndb:lg:grid-cols-[18rem_minmax(0,1fr)]"
    >
        <aside class="ndb:hidden ndb:min-w-0 ndb:border-r ndb:border-zinc-200 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950 ndb:lg:block">
            <div class="ndb:border-b ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800">
                <x-newdebugbar::search-field
                    label="Search components"
                    placeholder="Search components"
                    data-ndb-studio-search
                    autocomplete="off"
                />
            </div>

            <nav aria-label="Components" class="ndb:p-2">
                @foreach ($navigationGroups as $group => $metadata)
                    @php($selectedGroup = in_array($selected, $metadata['components'], true))
                    <details
                        data-ndb-studio-navigation-group="{{ $group }}"
                        data-ndb-studio-selected-group="{{ $selectedGroup ? 'true' : 'false' }}"
                        class="ndb:border-b ndb:border-zinc-200 ndb:last:border-b-0 ndb:dark:border-zinc-800"
                        @if ($selectedGroup) open @endif
                    >
                        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-2 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                            <span class="ndb:text-xs ndb:font-bold">{{ $metadata['title'] }}</span>
                            <span class="ndb:flex ndb:items-center ndb:gap-2 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                {{ count($metadata['components']) }}
                                <x-newdebugbar::icon
                                    name="chevron-down"
                                    size="3"
                                    class="ndb-details-chevron ndb:transition-transform"
                                />
                            </span>
                        </summary>

                        <p class="ndb:px-2 ndb:pb-2 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            {{ $metadata['description'] }}
                        </p>

                        <ul class="ndb:m-0 ndb:grid ndb:list-none ndb:gap-0.5 ndb:p-0 ndb:pb-2">
                            @foreach ($metadata['components'] as $component)
                                @php($entry = $components[$component])
                                <li
                                    data-ndb-studio-component="{{ $component }}"
                                    data-ndb-studio-search-text="{{ Illuminate\Support\Str::lower($entry['title'].' '.$component.' '.$entry['description'].' '.$entry['groupTitle']) }}"
                                >
                                    <a
                                        href="{{ route('newdebugbar.studio.component', ['component' => $component, 'theme' => $theme, 'width' => $previewWidth]) }}"
                                        data-ndb-studio-component-link="{{ $component }}"
                                        @class([
                                            'ndb:block ndb:rounded-lg ndb:px-2.5 ndb:py-2 ndb:no-underline ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500',
                                            'ndb:bg-indigo-50 ndb:text-indigo-950 ndb:dark:bg-indigo-950/55 ndb:dark:text-indigo-100' => $component === $selected,
                                            'ndb:text-zinc-700 ndb:hover:bg-zinc-100 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-900' => $component !== $selected,
                                        ])
                                        @if ($component === $selected) aria-current="page" @endif
                                    >
                                        <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold">{{ $entry['title'] }}</span>
                                        <code class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400 ndb:dark:text-zinc-500">{{ $component }}</code>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach

                <p
                    data-ndb-studio-no-results
                    hidden
                    class="ndb:px-2 ndb:py-8 ndb:text-center ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400"
                >
                    No components match that search.
                </p>
            </nav>
        </aside>

        <main class="ndb:min-w-0">
            <div class="ndb:border-b ndb:border-zinc-200 ndb:bg-white ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950 ndb:lg:hidden">
                <x-newdebugbar::select-field label="Choose a component" data-ndb-studio-component-select>
                    @foreach ($navigationGroups as $metadata)
                        <optgroup label="{{ $metadata['title'] }}">
                            @foreach ($metadata['components'] as $component)
                                <option
                                    value="{{ route('newdebugbar.studio.component', ['component' => $component]) }}"
                                    @selected($component === $selected)
                                >
                                    {{ $components[$component]['title'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-newdebugbar::select-field>
            </div>

            <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:bg-white ndb:px-3 ndb:py-2.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950 ndb:sm:px-4">
                <output
                    data-ndb-studio-width-output
                    class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                >
                    {{ $previewWidth }} px
                </output>

                <div class="ndb:grid ndb:min-w-0 ndb:flex-1 ndb:grid-cols-2 ndb:items-center ndb:gap-2 ndb:sm:flex ndb:sm:flex-none">
                    <x-newdebugbar::filter-tabs label="Preview theme" variant="segmented" class="ndb:w-full">
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

                    <x-newdebugbar::filter-tabs label="Preview width" variant="segmented" class="ndb:w-full">
                        <x-newdebugbar::filter-tab
                            variant="segmented"
                            data-ndb-studio-width="1024"
                            :aria-pressed="$previewWidth === 1024 ? 'true' : 'false'"
                        >Desktop</x-newdebugbar::filter-tab>
                        <x-newdebugbar::filter-tab
                            variant="segmented"
                            data-ndb-studio-width="390"
                            :aria-pressed="$previewWidth === 390 ? 'true' : 'false'"
                        >Mobile</x-newdebugbar::filter-tab>
                    </x-newdebugbar::filter-tabs>
                </div>
            </div>

            <div data-ndb-studio-canvas class="ndb:min-w-0 ndb:overflow-x-auto ndb:bg-zinc-100 ndb:dark:bg-black/35">
                <div
                    data-ndb-studio-frame-shell
                    aria-label="Resizable component preview"
                    class="ndb:mx-auto ndb:h-[max(32rem,calc(100vh-7.375rem))] ndb:min-w-80 ndb:resize-x ndb:overflow-hidden ndb:bg-white ndb:dark:bg-zinc-950 ndb:lg:h-[max(32rem,calc(100vh-3.75rem))]"
                    style="width: {{ $previewWidth }}px"
                >
                    <iframe
                        data-ndb-studio-frame
                        title="{{ $selectedComponent['title'] }} component preview"
                        src="{{ route('newdebugbar.studio.preview', ['component' => $selected, 'theme' => $theme]) }}"
                        class="ndb:block ndb:size-full ndb:border-0"
                    ></iframe>
                </div>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-ndb-studio]');
            const frame = root?.querySelector('[data-ndb-studio-frame]');
            const frameShell = root?.querySelector('[data-ndb-studio-frame-shell]');
            const widthOutput = root?.querySelector('[data-ndb-studio-width-output]');

            if (!root || !frame || !frameShell || !widthOutput) return;

            const currentWidth = () => Math.round(frame.getBoundingClientRect().width);

            const setPressed = (selector, dataKey, value) => {
                root.querySelectorAll(selector).forEach((control) => {
                    control.setAttribute('aria-pressed', String(control.dataset[dataKey] === value));
                });
            };

            const persistState = (key, value) => {
                const pageUrl = new URL(window.location.href);
                pageUrl.searchParams.set(key, value);
                window.history.replaceState({}, '', pageUrl);

                root.querySelectorAll('[data-ndb-studio-component-link]').forEach((link) => {
                    const url = new URL(link.href);
                    url.searchParams.set(key, value);
                    link.href = url.toString();
                });
            };

            const navigateToComponent = (target) => {
                const url = new URL(target, window.location.origin);
                url.searchParams.set('theme', root.dataset.ndbTheme);
                url.searchParams.set('width', String(currentWidth()));
                window.location.assign(url);
            };

            const reflectWidth = (width, persist = true) => {
                const value = String(Math.round(width));
                widthOutput.value = `${value} px`;
                setPressed('[data-ndb-studio-width]', 'ndbStudioWidth', value);

                if (persist) persistState('width', value);
            };

            const setWidth = (width) => {
                const bounded = Math.max(320, Math.min(1440, Number(width) || 1024));
                frameShell.style.width = `${bounded}px`;
                reflectWidth(bounded);
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
                    navigateToComponent(link.href);
                });
            });

            root.querySelector('[data-ndb-studio-component-select]')?.addEventListener('change', (event) => {
                navigateToComponent(event.currentTarget.value);
            });

            const search = root.querySelector('[data-ndb-studio-search]');
            const items = [...root.querySelectorAll('[data-ndb-studio-component]')];
            const navigationGroups = [...root.querySelectorAll('[data-ndb-studio-navigation-group]')];
            const noResults = root.querySelector('[data-ndb-studio-no-results]');

            search?.addEventListener('input', () => {
                const query = search.value.trim().toLocaleLowerCase();
                let visibleCount = 0;

                items.forEach((item) => {
                    const visible = item.dataset.ndbStudioSearchText.includes(query);
                    item.hidden = !visible;
                    if (visible) visibleCount += 1;
                });

                navigationGroups.forEach((group) => {
                    const hasVisibleComponent = Boolean(
                        group.querySelector('[data-ndb-studio-component]:not([hidden])'),
                    );
                    group.hidden = !hasVisibleComponent;
                    group.open = query ? hasVisibleComponent : group.dataset.ndbStudioSelectedGroup === 'true';
                });

                if (noResults) noResults.hidden = visibleCount > 0;
            });

            if (typeof ResizeObserver === 'function') {
                let resizeTimer;

                new ResizeObserver(() => {
                    const width = currentWidth();
                    reflectWidth(width, false);
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(() => persistState('width', String(width)), 120);
                }).observe(frameShell);
            }
        })();
    </script>
</body>
</html>
