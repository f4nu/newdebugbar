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

        if ($request->is('__newdebugbar/*') || $this->isLivewireRequest($request) || $this->isLivewireAsset($request)) {
            return false;
        }

        return true;
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
}
