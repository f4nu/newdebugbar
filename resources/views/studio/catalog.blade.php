<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $selectedComponent['title'] }} — New Debug Bar Studio preview</title>
    @livewireStyles
    <link rel="stylesheet" href="{{ $stylesheetUrl }}" />
</head>
<body class="ndb:m-0 ndb:min-h-screen ndb:bg-white ndb:dark:bg-zinc-950">
    <div
        id="newdebugbar"
        data-ndb-studio-preview="{{ $selected }}"
        data-ndb-studio-catalog="{{ $selectedComponent['group'] }}"
        data-ndb-theme="{{ $theme }}"
        x-data="newDebugBar(@js([
            'id' => 'newdebugbar-studio',
            'environment' => 'local',
            'method' => 'GET',
            'path' => '/__newdebugbar/studio/'.$selected.'/preview',
            'status' => 200,
            'query_count' => 34,
            'duration_ms' => 1453.51,
            'peak_memory_mb' => 8,
            'sections' => [],
        ]), 20)"
        class="ndb:min-h-screen ndb:bg-white ndb:text-zinc-950 ndb:dark:bg-zinc-950 ndb:dark:text-white"
    >
        <main class="ndb:min-h-screen">
            @include('newdebugbar::studio.demos.'.$selectedComponent['group'], [
                'components' => $selectedGroup['components'],
                'selected' => $selected,
                'selectedComponent' => $selectedComponent,
            ])
        </main>
    </div>

    <script src="{{ $scriptUrl }}"></script>
    @livewireScripts
    <script>
        window.newDebugBarHighlight?.(document.getElementById('newdebugbar'));
    </script>
</body>
</html>
