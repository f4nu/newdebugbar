<?php

namespace NewDebugBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\BarInjector;
use NewDebugBar\Support\RequestEligibility;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ProfileRequest
{
    public function __construct(
        private readonly ProfileManager $manager,
        private readonly ProfileStore $store,
        private readonly RequestEligibility $eligibility,
        private readonly BarInjector $injector,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->eligibility->allows($request)) {
            return $next($request);
        }

        try {
            $this->manager->begin($request);
        } catch (Throwable) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if ($profile = $this->finishSafely($request, exception: $exception)) {
                $this->storeSafely($profile);
            }

            throw $exception;
        }

        $profile = $this->finishSafely($request, $response);

        if ($profile === null) {
            return $response;
        }

        if ($id = $this->storeSafely($profile)) {
            $request->attributes->set('new-debug-bar.profile-id', $id);

            try {
                return $this->injector->inject($response, $id);
            } catch (Throwable) {
                return $response;
            }
        }

        return $response;
    }

    /** @return array<string, mixed>|null */
    private function finishSafely(
        Request $request,
        ?Response $response = null,
        ?Throwable $exception = null,
    ): ?array {
        try {
            return $this->manager->finish($request, $response, $exception);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $profile */
    private function storeSafely(array $profile): ?string
    {
        try {
            return $this->store->put($profile);
        } catch (Throwable) {
            return null;
        }
    }
}
