<?php

namespace NewDebugBar\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NewDebugBar\Livewire\BrowserTracePayload;
use NewDebugBar\Livewire\LivewireTraceAppender;
use NewDebugBar\Storage\ProfileStore;

/** Accepts one bounded signed browser trace without profiling the append request. */
final class LivewireTraceController
{
    public function __invoke(
        Request $request,
        string $profile,
        LivewireTraceAppender $appender,
    ): JsonResponse {
        if (! ProfileStore::validId($profile)) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $length = $request->headers->get('Content-Length');

        if ((is_numeric($length) && (int) $length > BrowserTracePayload::MAX_BYTES)
            || strlen($request->getContent()) > BrowserTracePayload::MAX_BYTES) {
            return response()->json(['status' => 'too_large'], 413);
        }

        $revision = filter_var($request->query('revision'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $nonce = $request->query('nonce');

        if (! is_int($revision) || ! is_string($nonce) || ! ProfileStore::validId($nonce)) {
            return response()->json(['status' => 'malformed'], 422);
        }

        $payload = $request->json()->all();
        $result = $appender->append($profile, $revision, $nonce, is_array($payload) ? $payload : []);
        $status = match ($result['status']) {
            'accepted' => 202,
            'not_found' => 404,
            'conflict', 'repeated' => 409,
            'unavailable' => 503,
            default => 422,
        };

        return response()->json($result, $status);
    }
}
