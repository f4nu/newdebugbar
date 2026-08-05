<?php

namespace NewDebugBar\Support;

use Livewire\LivewireManager;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Adds the Livewire toolbar and package assets to supported HTML responses. */
final class BarInjector
{
    public function __construct(
        private readonly LivewireManager $livewire,
        private readonly AssetUrl $assets,
    ) {}

    public function inject(Response $response, string $profileId): Response
    {
        if (! $this->supports($response)) {
            return $response;
        }

        $html = (string) $response->getContent();

        $stylesheet = e($this->assets->for('new-debug-bar.css'));
        $script = e($this->assets->for('new-debug-bar.js'));
        $component = $this->livewire->mount('new-debug-bar.toolbar', ['profileId' => $profileId], 'new-debug-bar-toolbar');
        $livewireStyles = FrontendAssets::styles();
        $livewireScripts = FrontendAssets::scripts();

        $head = $livewireStyles
            .'<style id="new-debug-bar-critical-css" data-navigate-once="true">#new-debug-bar [x-cloak]{display:none!important}</style>'
            .'<link rel="stylesheet" href="'.$stylesheet.'" data-navigate-once="true">';
        $body = $livewireScripts
            .'<script src="'.$script.'" data-navigate-once="true"></script>'.$component;
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

    public function supports(Response $response): bool
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ($response->isRedirection()) {
            return false;
        }

        if (str_contains(strtolower((string) $response->headers->get('Content-Disposition')), 'attachment')) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        return preg_match('/<\/body\s*>/i', (string) $response->getContent()) === 1;
    }
}
