<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $selectedGroup['title'] }} — New Debug Bar Studio</title>
    @livewireStyles
    <link rel="stylesheet" href="{{ $stylesheetUrl }}" />
</head>
<body class="ndb:m-0 ndb:min-h-screen ndb:bg-white ndb:dark:bg-zinc-950">
    <div
        id="newdebugbar"
        data-ndb-studio-catalog="{{ $selected }}"
        data-ndb-theme="{{ $theme }}"
        x-data="newDebugBar(@js([
            'id' => 'newdebugbar-studio',
            'environment' => 'local',
            'method' => 'GET',
            'path' => '/__newdebugbar/studio',
            'status' => 200,
            'query_count' => 34,
            'duration_ms' => 1453.51,
            'peak_memory_mb' => 8,
            'sections' => [],
        ]), 20)"
        class="ndb:min-h-screen ndb:bg-white ndb:text-zinc-950 ndb:dark:bg-zinc-950 ndb:dark:text-white"
    >
        <header class="ndb:sticky ndb:top-0 ndb:z-20 ndb:border-b ndb:border-zinc-200/90 ndb:bg-white/95 ndb:px-4 ndb:py-4 ndb:backdrop-blur ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/95 ndb:sm:px-6">
            <h1 class="ndb:text-base ndb:font-bold">{{ $selectedGroup['title'] }}</h1>
            <p class="ndb:mt-0.5 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                {{ $selectedGroup['description'] }}
            </p>
        </header>

        <main class="ndb:space-y-5 ndb:p-4 ndb:sm:p-6">
            @include('newdebugbar::studio.demos.'.$selected, [
                'components' => $selectedGroup['components'],
            ])
        </main>
    </div>

    <script src="{{ $scriptUrl }}"></script>
    @livewireScripts
    <script>
        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin || event.data?.type !== 'newdebugbar:studio-scroll') return;

            document.getElementById(`newdebugbar-studio-${event.data.component}`)?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        });

        window.newDebugBarHighlight?.(document.getElementById('newdebugbar'));
    </script>
</body>
</html>
