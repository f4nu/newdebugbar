<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Livewire\Component;
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
        on('dehydrate', function (Component $component): void {
            $this->recorder()->observeDehydrate($component);
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

    private function recorder(): InteractionRecorder
    {
        return $this->app->make(InteractionRecorder::class);
    }
}
