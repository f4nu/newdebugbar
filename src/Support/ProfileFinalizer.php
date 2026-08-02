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
    ) {}

    public function handle(RequestHandled $event): void
    {
        if (! $this->manager->isCollecting()) {
            return;
        }

        if (! $this->injector->supports($event->response)) {
            $this->manager->discard();

            return;
        }

        try {
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

        try {
            $this->injector->inject($event->response, $id);
        } catch (Throwable) {
            // Debug rendering must never replace the application response.
        }
    }
}
