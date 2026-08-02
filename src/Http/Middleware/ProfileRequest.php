<?php

namespace NewDebugBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;
use NewDebugBar\Support\BarInjector;
use NewDebugBar\Support\RequestEligibility;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Profiles eligible web responses and injects their debug interface. */
final class ProfileRequest
{
    public function __construct(
        private readonly ProfileManager $manager,
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
            $this->injector->prepareAssets();
        } catch (Throwable) {
            return $next($request);
        }

        try {
            return $next($request);
        } catch (Throwable $exception) {
            $this->manager->recordException($exception);

            throw $exception;
        }
    }
}
