<?php

namespace NewDebugBar\Support;

use Illuminate\Foundation\Http\Events\RequestHandled;
use NewDebugBar\Livewire\LivewireTraceToken;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use Throwable;

/** Stores and injects a profile after Laravel has rendered the final response. */
final class ProfileFinalizer
{
    public function __construct(
        private readonly ProfileManager $manager,
        private readonly ProfileStore $store,
        private readonly BarInjector $injector,
        private readonly LivewireTraceToken $traceTokens,
    ) {}

    public function handle(RequestHandled $event): void
    {
        if (! $this->manager->isCollecting()) {
            return;
        }

        try {
            $profile = $this->manager->finish($event->request, $event->response);
        } catch (Throwable) {
            $this->manager->discard();

            return;
        }

        try {
            $id = $this->store->put($profile);
        } catch (Throwable) {
            return;
        }

        $event->request->attributes->set('newdebugbar.profile-id', $id);
        $event->response->headers->set('X-NewDebugBar-Profile', $id);

        if ($event->request->headers->has('X-Livewire') && isset($profile['sections']['livewire'])) {
            try {
                $event->response->headers->set(
                    LivewireTraceToken::HEADER,
                    $this->traceTokens->issue(
                        $id,
                        (int) ($profile['sections']['livewire']['profile_revision'] ?? 1),
                    ),
                );
            } catch (Throwable) {
                // A missing trace token must never replace the application response.
            }
        }

        if ($this->injector->supports($event->response)) {
            try {
                $this->injector->inject($event->response, $id);
            } catch (Throwable) {
                // Debug rendering must never replace the application response.
            }
        }
    }
}
