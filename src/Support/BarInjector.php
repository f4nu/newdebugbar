<?php

namespace NewDebugBar\Support;

use Livewire\LivewireManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BarInjector
{
    public function __construct(
        private readonly LivewireManager $livewire,
        private readonly AssetUrl $assets,
    ) {}

    public function inject(Response $response, string $profileId): Response
    {
        if (! $this->canInject($response)) {
            return $response;
        }

        $html = (string) $response->getContent();

        if (preg_match('/<\/body\s*>/i', $html) !== 1) {
            return $response;
        }

        $stylesheet = e($this->assets->for('new-debug-bar.css'));
        $script = e($this->assets->for('new-debug-bar.js'));
        $component = $this->livewire->mount('new-debug-bar.toolbar', ['profileId' => $profileId], 'new-debug-bar-'.$profileId);

        $this->livewire->forceAssetInjection();

        $head = '<link rel="stylesheet" href="'.$stylesheet.'" data-navigate-once="true">';
        $body = '<script src="'.$script.'" data-navigate-once="true"></script>'.$component;
        if (preg_match('/<\/head\s*>/i', $html) === 1) {
            $html = preg_replace('/<\/head\s*>/i', $head.'$0', $html, 1) ?? $html;
        } elseif (preg_match('/<html(?:\s[^>]*)?>/i', $html) === 1) {
            $html = preg_replace('/<html(?:\s[^>]*)?>/i', '$0<head>'.$head.'</head>', $html, 1) ?? $html;
        }

        $html = preg_replace('/<\/body\s*>/i', $body.'$0', $html, 1) ?? $html;

        $response->setContent($html);
        $response->headers->remove('Content-Length');
        $response->headers->set('X-New-Debug-Bar-Profile', $profileId);

        return $response;
    }

    private function canInject(Response $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if (str_contains((string) $response->headers->get('Content-Disposition'), 'attachment')) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        if ($contentType !== '') {
            return str_contains($contentType, 'text/html');
        }

        return str_contains(strtolower((string) $response->getContent()), '</html>');
    }
}
