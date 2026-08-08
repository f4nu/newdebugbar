<?php

namespace NewDebugBar\Support;

use Illuminate\Foundation\Http\Events\RequestHandled;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        private readonly StreamedProfileCapture $streamedProfiles,
    ) {}

    public function handle(RequestHandled $event): void
    {
        if (! $this->manager->isCollecting()) {
            return;
        }

        if ($event->response instanceof StreamedResponse) {
            if (! config('newdebugbar.capture_streamed', false)) {
                $this->manager->discard();

                return;
            }

            try {
                if (! $this->streamedProfiles->prepare($event->request, $event->response)) {
                    $this->manager->discard();
                }
            } catch (Throwable) {
                $this->manager->discard();
            }

            return;
        }

        $livewire = $this->eligibility->isApplicationLivewireRequest($event->request);

        try {
            if ($livewire) {
                $payload = json_decode((string) $event->response->getContent(), true);

                if (is_array($payload)) {
                    $this->livewireUpdates->record($payload, $event->request);
                }
            }

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

        if (! $livewire && $this->injector->supports($event->response)) {
            try {
                $this->injector->inject($event->response, $id);
            } catch (Throwable) {
                // Debug rendering must never replace the application response.
            }
        }
    }
}
