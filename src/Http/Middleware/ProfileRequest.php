<?php

namespace NewDebugBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use NewDebugBar\ProfileManager;
use NewDebugBar\Support\RequestEligibility;
use NewDebugBar\Support\StreamedProfileCapture;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Profiles eligible Laravel requests without changing unsupported response bodies. */
final class ProfileRequest
{
    public function __construct(
        private readonly ProfileManager $manager,
        private readonly RequestEligibility $eligibility,
        private readonly StreamedProfileCapture $streamedProfiles,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->manager->isCollecting() || ! $this->eligibility->allows($request)) {
            return $next($request);
        }

        try {
            $this->manager->begin($request);
        } catch (Throwable) {
            $this->manager->discard();

            return $next($request);
        }

        try {
            return $next($request);
        } catch (Throwable $exception) {
            if (! $exception instanceof ValidationException) {
                $this->manager->recordException($exception);
            }

            throw $exception;
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->streamedProfiles->terminate($request, $response);
    }
}
