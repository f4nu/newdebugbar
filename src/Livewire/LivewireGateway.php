<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use ReflectionClass;
use Throwable;

use function Livewire\on;

/** Isolates the small documented and internal Livewire contracts used by diagnostics. */
final class LivewireGateway
{
    public const NOT_LIVEWIRE = 'not_livewire';

    public const HOST_APPLICATION = 'host_application';

    public const PACKAGE_TOOLBAR = 'package_toolbar';

    public const MALFORMED = 'malformed';

    private bool $registered = false;

    public function __construct(private readonly Container $app) {}

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        on('mount', function (Component $component, array $params, mixed $key, mixed $parent): void {
            $this->recorder()->observeMount($component, $params, $key, $parent);
        });
        on('hydrate', function (Component $component): void {
            $this->recorder()->observeHydrate($component);
        });
        on('update', function (Component $component, string $path): callable {
            $token = $this->recorder()->observeUpdate($component, $path);

            return fn () => $this->recorder()->popContext($token);
        });
        on('call', function (Component $component, string $method, array $params): callable {
            $token = $this->recorder()->observeCall($component, $method, $params);

            return fn () => $this->recorder()->popContext($token);
        });
        on('render', function (Component $component, mixed $view): callable {
            $token = $this->recorder()->observeRender($component, $view);

            return fn () => $this->recorder()->popContext($token);
        });
        on('dehydrate', function (Component $component, ComponentContext $context): void {
            $this->recorder()->observeDehydrate($component, is_array($context->effects) ? $context->effects : []);
        });
        on('destroy', function (Component $component): void {
            $this->recorder()->observeDestroy($component);
        });
        on('profile', function (string $phase, string $componentId, array $range): void {
            $this->recorder()->observeServerSpan($phase, $componentId, $range);
        });
        on('response', function (array $payload): void {
            $this->recorder()->observeResponse($payload);
        });
    }

    public function requestOwner(Request $request): string
    {
        if (! $request->headers->has('X-Livewire')) {
            return self::NOT_LIVEWIRE;
        }

        $messages = $request->input('components');

        if (! is_array($messages) || $messages === []) {
            return self::MALFORMED;
        }

        $hostMessage = false;
        $toolbarMessage = false;

        foreach ($messages as $message) {
            if (! is_array($message) || ! is_string($message['snapshot'] ?? null)) {
                return self::MALFORMED;
            }

            try {
                $snapshot = json_decode($message['snapshot'], true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return self::MALFORMED;
            }

            $name = is_array($snapshot) ? data_get($snapshot, 'memo.name') : null;

            if (! is_string($name) || $name === '') {
                return self::MALFORMED;
            }

            if ($name === 'newdebugbar.toolbar') {
                $toolbarMessage = true;
            } else {
                $hostMessage = true;
            }
        }

        if ($hostMessage) {
            return self::HOST_APPLICATION;
        }

        return $toolbarMessage ? self::PACKAGE_TOOLBAR : self::MALFORMED;
    }

    /**
     * Resolve the developer-owned source behind class, single-file, and multi-file components.
     *
     * The Livewire finder is an internal contract, so it is contained here and always falls
     * back to reflection. A missing or changed finder returns truthful unknown evidence.
     *
     * @return array{path: string, line: int, kind: string}|null
     */
    public function componentSource(Component $component): ?array
    {
        try {
            $finder = $this->app->bound('livewire.finder')
                ? $this->app->make('livewire.finder')
                : null;
            $name = $component->getName();

            if (is_object($finder) && is_string($name) && $name !== '') {
                if (method_exists($finder, 'resolveMultiFileComponentPath')) {
                    $directory = $finder->resolveMultiFileComponentPath($name);

                    if (is_string($directory) && is_dir($directory)) {
                        $files = array_values(array_filter(
                            glob(rtrim($directory, '/\\').'/*.php') ?: [],
                            fn (string $file): bool => ! str_ends_with($file, '.blade.php'),
                        ));
                        sort($files);

                        if (isset($files[0]) && is_file($files[0])) {
                            return ['path' => $files[0], 'line' => 1, 'kind' => 'multi_file'];
                        }
                    }
                }

                if (method_exists($finder, 'resolveSingleFileComponentPath')) {
                    $path = $finder->resolveSingleFileComponentPath($name);

                    if (is_string($path) && is_file($path)) {
                        return ['path' => $path, 'line' => 1, 'kind' => 'single_file'];
                    }
                }
            }
        } catch (Throwable) {
            // The finder is optional internal evidence. Reflection remains available below.
        }

        try {
            $reflection = new ReflectionClass($component);
            $filename = $reflection->getFileName();

            return is_string($filename)
                ? ['path' => $filename, 'line' => $reflection->getStartLine(), 'kind' => 'class']
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function recorder(): InteractionRecorder
    {
        return $this->app->make(InteractionRecorder::class);
    }
}
