<?php

namespace NewDebugBar\Support;

use Composer\InstalledVersions;
use Illuminate\Foundation\Application;

/** Builds safe, host-derived runtime and installed-package facts. */
final class RuntimeContext
{
    /** @var array<string, array{label: string, packages: list<string>}> */
    private const ECOSYSTEM = [
        'cashier' => ['label' => 'Cashier', 'packages' => ['laravel/cashier']],
        'filament' => ['label' => 'Filament', 'packages' => ['filament/filament']],
        'flux' => ['label' => 'Flux', 'packages' => ['livewire/flux-pro', 'livewire/flux']],
        'fortify' => ['label' => 'Fortify', 'packages' => ['laravel/fortify']],
        'horizon' => ['label' => 'Horizon', 'packages' => ['laravel/horizon']],
        'inertia' => ['label' => 'Inertia', 'packages' => ['inertiajs/inertia-laravel']],
        'octane' => ['label' => 'Octane', 'packages' => ['laravel/octane']],
        'pennant' => ['label' => 'Pennant', 'packages' => ['laravel/pennant']],
        'pulse' => ['label' => 'Pulse', 'packages' => ['laravel/pulse']],
        'reverb' => ['label' => 'Reverb', 'packages' => ['laravel/reverb']],
        'sanctum' => ['label' => 'Sanctum', 'packages' => ['laravel/sanctum']],
        'scout' => ['label' => 'Scout', 'packages' => ['laravel/scout']],
        'telescope' => ['label' => 'Telescope', 'packages' => ['laravel/telescope']],
    ];

    public function __construct(private readonly Application $app) {}

    /** @return array<string, mixed> */
    public function build(bool $livewireActivity): array
    {
        $ecosystem = $this->ecosystem();

        if ($livewireActivity && InstalledVersions::isInstalled('livewire/livewire')) {
            $ecosystem[] = $this->package('livewire', 'Livewire', 'livewire/livewire');
        }

        usort($ecosystem, fn (array $left, array $right): int => $left['label'] <=> $right['label']);

        return [
            'runtime' => [
                'environment' => (string) config('app.env', 'unknown'),
                'php' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'runtime_type' => $this->runtimeType(),
                'laravel' => $this->app->version(),
                'debug' => (bool) config('app.debug', false),
                'locale' => $this->app->getLocale(),
                'timezone' => (string) config('app.timezone', 'UTC'),
            ],
            'cache_state' => [
                'configuration' => $this->app->configurationIsCached(),
                'routes' => $this->app->routesAreCached(),
                'events' => $this->app->eventsAreCached(),
                'views' => null,
            ],
            'drivers' => array_filter([
                'database' => $this->driver('database.default'),
                'cache' => $this->driver('cache.default'),
                'queue' => $this->driver('queue.default'),
                'session' => $this->driver('session.driver'),
                'mail' => $this->driver('mail.default'),
                'broadcasting' => $this->driver('broadcasting.default'),
            ], fn (?string $driver): bool => $driver !== null),
            'ecosystem' => $ecosystem,
            'package' => $this->packageVersion(),
        ];
    }

    private function packageVersion(): string
    {
        foreach (['newdebugbar/newdebugbar', 'newdebugbar/new-debug-bar'] as $package) {
            if (InstalledVersions::isInstalled($package)) {
                return InstalledVersions::getPrettyVersion($package) ?? 'dev';
            }
        }

        return 'dev';
    }

    /** @return list<array{key: string, label: string, version: string}> */
    private function ecosystem(): array
    {
        $packages = [];

        foreach (self::ECOSYSTEM as $key => $definition) {
            foreach ($definition['packages'] as $package) {
                if (InstalledVersions::isInstalled($package)) {
                    $packages[] = $this->package($key, $definition['label'], $package);

                    break;
                }
            }
        }

        return $packages;
    }

    /** @return array{key: string, label: string, version: string} */
    private function package(string $key, string $label, string $package): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'version' => InstalledVersions::getPrettyVersion($package) ?? 'installed',
        ];
    }

    private function driver(string $key): ?string
    {
        $driver = config($key);

        return is_string($driver) && $driver !== '' ? $driver : null;
    }

    private function runtimeType(): string
    {
        if (isset($_SERVER['LARAVEL_OCTANE'])) {
            return 'Octane';
        }

        if (PHP_SAPI === 'frankenphp' || extension_loaded('frankenphp')) {
            return 'FrankenPHP';
        }

        if (getenv('RR_MODE') !== false) {
            return 'RoadRunner';
        }

        return match (PHP_SAPI) {
            'fpm-fcgi' => 'FPM',
            'cli' => 'CLI',
            default => PHP_SAPI,
        };
    }
}
