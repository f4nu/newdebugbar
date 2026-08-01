<?php

namespace NewDebugBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\RequestEligibility;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ProfileRequest
{
    public function __construct(
        private readonly ProfileManager $manager,
        private readonly ProfileStore $store,
        private readonly RequestEligibility $eligibility,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->eligibility->allows($request)) {
            return $next($request);
        }

        $this->manager->begin($request);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->storeSafely($this->manager->finish($request, exception: $exception));

            throw $exception;
        }

        $profile = $this->manager->finish($request, $response);

        if ($id = $this->storeSafely($profile)) {
            $request->attributes->set('new-debug-bar.profile-id', $id);
        }

        return $response;
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
