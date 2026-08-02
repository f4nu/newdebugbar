<?php

namespace NewDebugBar\Support;

use Illuminate\Foundation\Http\Events\RequestHandled;
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
        private readonly RequestEligibility $eligibility,
        private readonly LivewireUpdateRecorder $livewireUpdates,
    ) {}

    public function handle(RequestHandled $event): void
    {
        if (! $this->manager->isCollecting()) {
            return;
        }

        $livewire = $this->eligibility->isApplicationLivewireRequest($event->request);

        if (! $livewire && ! $this->injector->supports($event->response)) {
            $this->manager->discard();

            return;
        }

        try {
            if ($livewire) {
                $payload = json_decode((string) $event->response->getContent(), true);

                if (is_array($payload)) {
                    $this->livewireUpdates->record($payload, $event->request);
                }
            }

            $profile = $this->manager->finish($event->request, $event->response);
        } catch (Throwable) {
            return;
        }

        try {
            $id = $this->store->put($profile);
        } catch (Throwable) {
            return;
        }

        $event->request->attributes->set('new-debug-bar.profile-id', $id);

        if ($livewire) {
            $event->response->headers->set('X-New-Debug-Bar-Profile', $id);
        } else {
            try {
                $this->injector->inject($event->response, $id);
            } catch (Throwable) {
                // Debug rendering must never replace the application response.
            }
        }
    }
}
