<?php

namespace NewDebugBar\Support;

use Illuminate\Http\Request;
use NewDebugBar\Livewire\LivewireGateway;

/** Decides whether an application request can produce a safe stored profile. */
final class RequestEligibility
{
    public function __construct(private readonly LivewireGateway $livewire) {}

    public function allows(Request $request): bool
    {
        if (! config('newdebugbar.enabled', true)) {
            return false;
        }

        if ($request->is('__newdebugbar/*') || $this->isLivewireAsset($request)) {
            return false;
        }

        if ($request->headers->has('X-Livewire')) {
            return $this->livewire->requestOwner($request) === LivewireGateway::HOST_APPLICATION;
        }

        return true;
    }

    private function isLivewireAsset(Request $request): bool
    {
        return $request->isMethod('GET')
            && preg_match('#\Alivewire-[0-9a-f]{8}/livewire(?:\.min)?\.js\z#i', $request->path()) === 1;
    }
}
