<?php

namespace NewDebugBar\Support;

use Illuminate\Http\Request;

/** Decides whether an application request can produce a safe stored profile. */
final class RequestEligibility
{
    public function allows(Request $request): bool
    {
        if (! config('newdebugbar.enabled', true)) {
            return false;
        }

        if ($request->is('__newdebugbar/*') || $this->isLivewireAsset($request)) {
            return false;
        }

        return ! $this->isLivewireRequest($request) || $this->isApplicationLivewireRequest($request);
    }

    public function isApplicationLivewireRequest(Request $request): bool
    {
        $names = $this->livewireComponentNames($request);

        return $this->isLivewireRequest($request)
            && $names !== null
            && collect($names)->contains(fn (string $name): bool => $name !== 'newdebugbar.toolbar');
    }

    private function isLivewireRequest(Request $request): bool
    {
        return $request->headers->has('X-Livewire');
    }

    private function isLivewireAsset(Request $request): bool
    {
        return $request->isMethod('GET')
            && preg_match('#\Alivewire-[0-9a-f]{8}/livewire(?:\.min)?\.js\z#i', $request->path()) === 1;
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
