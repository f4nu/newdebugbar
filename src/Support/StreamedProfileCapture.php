<?php

namespace NewDebugBar\Support;

use Closure;
use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Finishes an opted-in request profile after its streamed response callback returns. */
final class StreamedProfileCapture
{
    private ?Request $request = null;

    private ?StreamedResponse $response = null;

    private bool $completed = false;

    private bool $terminated = false;

    private bool $finalized = false;

    public function __construct(
        private readonly ProfileManager $manager,
        private readonly ProfileStore $store,
    ) {}

    public function prepare(Request $request, StreamedResponse $response): bool
    {
        if ($this->response === $response && $this->request === $request) {
            return true;
        }

        if ($this->response !== null || $this->finalized) {
            return false;
        }

        $callback = $this->callback($response);

        if ($callback === null) {
            return false;
        }

        $this->request = $request;
        $this->response = $response;
        $response->setCallback(function () use ($callback): void {
            try {
                $callback();
            } catch (Throwable $exception) {
                try {
                    $this->manager->recordException($exception);
                } catch (Throwable) {
                    // Profiling must not replace the stream exception.
                }

                throw $exception;
            } finally {
                $this->completed = true;
                $this->finalizeIfReady();
            }
        });

        return true;
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->request !== $request || $this->response !== $response || $this->finalized) {
            return;
        }

        $this->terminated = true;
        $this->finalizeIfReady();
    }

    private function callback(StreamedResponse $response): ?Closure
    {
        if (method_exists($response, 'getCallback')) {
            return $response->getCallback();
        }

        try {
            $callback = (new ReflectionProperty($response, 'callback'))->getValue($response);

            return is_callable($callback) ? Closure::fromCallable($callback) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function finalizeIfReady(): void
    {
        if (! $this->completed || ! $this->terminated || $this->finalized
            || $this->request === null || $this->response === null) {
            return;
        }

        $this->finalized = true;

        try {
            $profile = $this->manager->finish($this->request, $this->response);
        } catch (Throwable) {
            $this->manager->discard();

            return;
        }

        try {
            $id = $this->store->put($profile);
        } catch (Throwable) {
            return;
        }

        $this->request->attributes->set('newdebugbar.profile-id', $id);
    }
}
