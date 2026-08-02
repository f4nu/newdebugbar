<?php

namespace NewDebugBar\Support;

use Illuminate\Http\Request;

/** Decides whether a request can produce an in-page debug toolbar. */
final class RequestEligibility
{
    public function allows(Request $request): bool
    {
        if (! config('new-debug-bar.enabled', true)) {
            return false;
        }

        if ($this->isLivewireRequest($request)) {
            return $this->isApplicationLivewireRequest($request);
        }

        if ($request->expectsJson() || $request->is('__new-debug-bar/*') || $request->is('livewire/*')) {
            return false;
        }

        return $request->acceptsHtml();
    }

    public function isApplicationLivewireRequest(Request $request): bool
    {
        $names = $this->livewireComponentNames($request);

        return $this->isLivewireRequest($request)
            && $names !== null
            && collect($names)->contains(fn (string $name): bool => $name !== 'new-debug-bar.toolbar');
    }

    private function isLivewireRequest(Request $request): bool
    {
        return $request->headers->has('X-Livewire');
    }

    /** @return list<string>|null */
    private function livewireComponentNames(Request $request): ?array
    {
        $components = $request->input('components');

        if (! is_array($components) || $components === []) {
            return null;
        }

        $names = [];

        foreach ($components as $component) {
            if (! is_array($component) || ! is_string($component['snapshot'] ?? null)) {
                return null;
            }

            $snapshot = json_decode($component['snapshot'], true);
            $name = $snapshot['memo']['name'] ?? null;

            if (! is_string($name) || $name === '') {
                return null;
            }

            $names[] = $name;
        }

        return $names;
    }
}
