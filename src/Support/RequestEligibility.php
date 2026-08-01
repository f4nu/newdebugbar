<?php

namespace NewDebugBar\Support;

use Illuminate\Http\Request;

final class RequestEligibility
{
    public function allows(Request $request): bool
    {
        if (! config('new-debug-bar.enabled', true)) {
            return false;
        }

        if ($request->expectsJson() || $request->headers->has('X-Livewire')) {
            return false;
        }

        if ($request->is('__new-debug-bar/*') || $request->is('livewire/*')) {
            return false;
        }

        return $request->acceptsHtml();
    }
}
