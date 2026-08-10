<?php

namespace NewDebugBar\Livewire;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;

/** Issues one short-lived signed append URL for an exact profile revision. */
final class LivewireTraceToken
{
    public const HEADER = 'X-NewDebugBar-Livewire-Trace';

    public function __construct(private readonly UrlGenerator $urls) {}

    public function issue(string $profileId, int $revision): string
    {
        return $this->urls->temporarySignedRoute(
            'newdebugbar.livewire-trace',
            now()->addSeconds(30),
            [
                'profile' => $profileId,
                'revision' => max(1, $revision),
                'nonce' => (string) Str::uuid(),
            ],
        );
    }
}
