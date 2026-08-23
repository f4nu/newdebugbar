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
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
        ];

        if (in_array($format, ['html', 'text'], true)) {
            $nonce = bin2hex(random_bytes(16));
            $document = $format === 'html'
                ? $content
                : $this->textDocument($content);

            return response($this->withHeightReporter($document, $nonce), 200, [
                ...$headers,
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Security-Policy' => "sandbox allow-scripts; default-src 'none'; img-src data:; style-src 'unsafe-inline'; script-src 'nonce-{$nonce}'; script-src-attr 'none'; form-action 'none'; base-uri 'none'; frame-ancestors 'self'",
            ]);
        }

        return response($content, 200, [
            ...$headers,
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="message-'.($index + 1).'.eml"',
        ]);
    }

    private function withHeightReporter(string $content, string $nonce): string
    {
        $reporter = <<<'HTML'
<script nonce="__NONCE__">
(() => {
    let scheduled = false;
    const report = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
            scheduled = false;
            const height = Math.max(
                320,
                document.body?.scrollHeight ?? 0,
                document.documentElement.scrollHeight,
            );
            window.parent.postMessage({
                type: 'newdebugbar:mail-preview-height',
                height,
            }, '*');
        });
    };

    window.addEventListener('message', (event) => {
        if (event.data?.type === 'newdebugbar:measure-mail-preview') report();
    });
    window.addEventListener('wheel', (event) => {
        window.parent.postMessage({
            type: 'newdebugbar:mail-preview-scroll',
            deltaY: event.deltaY,
            deltaMode: event.deltaMode,
        }, '*');
    }, { passive: true });
    window.addEventListener('keydown', (event) => {
        const deltaY = {
            ArrowDown: 48,
            ArrowUp: -48,
            PageDown: window.innerHeight * 0.8,
            PageUp: window.innerHeight * -0.8,
            Home: -100000,
            End: 100000,
        }[event.key];
        if (deltaY === undefined || event.altKey || event.ctrlKey || event.metaKey) return;
        event.preventDefault();
        window.parent.postMessage({
            type: 'newdebugbar:mail-preview-scroll',
            deltaY,
            deltaMode: 0,
        }, '*');
    });
    let lastTouchY = null;
    window.addEventListener('touchstart', (event) => {
        lastTouchY = event.touches[0]?.clientY ?? null;
    }, { passive: true });
    window.addEventListener('touchmove', (event) => {
        const currentY = event.touches[0]?.clientY ?? null;
        if (lastTouchY === null || currentY === null) return;
        window.parent.postMessage({
            type: 'newdebugbar:mail-preview-scroll',
            deltaY: lastTouchY - currentY,
            deltaMode: 0,
        }, '*');
        lastTouchY = currentY;
    }, { passive: true });
    window.addEventListener('touchend', () => {
        lastTouchY = null;
    }, { passive: true });
    window.addEventListener('load', report);
    if (typeof ResizeObserver === 'function') {
        new ResizeObserver(report).observe(document.body);
    }
    report();
})();
</script>
HTML;
        $reporter = str_replace('__NONCE__', $nonce, $reporter);
        $closingBody = strripos($content, '</body>');

        if ($closingBody === false) {
            return $content.$reporter;
        }

        return substr($content, 0, $closingBody).$reporter.substr($content, $closingBody);
    }

    private function textDocument(string $content): string
    {
        $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Text mail preview</title>
</head>
<body style="margin: 0; background: #ffffff; color: #27272a;">
    <pre style="box-sizing: border-box; min-height: 100vh; margin: 0; padding: 24px; white-space: pre-wrap; overflow-wrap: anywhere; font: 14px/1.65 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">{$escaped}</pre>
</body>
</html>
HTML;
    }
}
