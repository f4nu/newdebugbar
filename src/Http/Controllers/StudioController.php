<?php

namespace NewDebugBar\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use NewDebugBar\Presentation\StudioCatalog;
use NewDebugBar\Support\AssetUrl;

/** Renders the local living catalog for New Debug Bar UI components. */
final class StudioController
{
    public function __construct(private readonly AssetUrl $assets) {}

    public function index(Request $request): View
    {
        return $this->render($request, StudioCatalog::DEFAULT_COMPONENT, false);
    }

    public function show(Request $request, string $component): View
    {
        return $this->render($request, $component, false);
    }

    public function preview(Request $request, string $component): View
    {
        return $this->render($request, $component, true);
    }

    private function render(Request $request, string $component, bool $preview): View
    {
        $components = StudioCatalog::components();
        $selectedComponent = $components[$component] ?? null;

        if ($selectedComponent === null) {
            abort(404);
        }

        $theme = $request->query('theme', 'light');
        $theme = is_string($theme) && in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        $requestedWidth = $request->query('width', 1024);
        $previewWidth = is_numeric($requestedWidth) ? (int) $requestedWidth : 1024;
        $previewWidth = max(320, min(1440, $previewWidth));
        $view = $preview ? 'newdebugbar::studio.catalog' : 'newdebugbar::studio.shell';

        return view($view, [
            'navigationGroups' => StudioCatalog::navigationGroups(),
            'components' => $components,
            'selected' => $component,
            'selectedComponent' => $selectedComponent,
            'theme' => $theme,
            'previewWidth' => $previewWidth,
            'stylesheetUrl' => $this->assets->for('newdebugbar.css'),
            'scriptUrl' => $this->assets->for('newdebugbar.js'),
        ]);
    }
}
