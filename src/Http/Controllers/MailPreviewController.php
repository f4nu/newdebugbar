<?php

namespace NewDebugBar\Http\Controllers;

use Illuminate\Http\Response;
use NewDebugBar\Storage\ProfileStore;

/** Serves retained mail previews from local profile storage. */
final class MailPreviewController
{
    public function __invoke(string $profile, int $index, string $format, ProfileStore $store): Response
    {
        $environments = config('newdebugbar.environments', ['local']);

        if (! is_array($environments)
            || ! app()->environment($environments)) {
            abort(404);
        }

        $stored = $store->get($profile);
        $preview = $stored['sections']['mail']['payload']['items'][$index]['preview'] ?? null;

        if (! is_array($preview)) {
            abort(404);
        }

        $content = $preview[$format] ?? null;

        if (! is_string($content)) {
            abort(404);
        }

        $headers = [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ];

        if ($format === 'html') {
            return response($content, 200, [
                ...$headers,
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Security-Policy' => "sandbox; default-src 'none'; img-src data:; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'; frame-ancestors 'none'",
            ]);
        }

        if ($format === 'text') {
            return response($content, 200, [...$headers, 'Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($content, 200, [
            ...$headers,
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="message-'.($index + 1).'.eml"',
        ]);
    }
}
