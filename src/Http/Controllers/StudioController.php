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

    public function __invoke(Request $request): View
    {
        $groups = StudioCatalog::groups();
        $preview = $request->query('preview');
        $selected = $preview ?? $request->query('group', array_key_first($groups));

        if (! is_string($selected) || ! isset($groups[$selected])) {
            abort(404);
        }

        $theme = $request->query('theme', 'light');
        $theme = is_string($theme) && in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        $previewWidth = (int) $request->query('width', 1024);
        $previewWidth = in_array($previewWidth, [390, 1024], true) ? $previewWidth : 1024;
        $view = $preview === null ? 'newdebugbar::studio.shell' : 'newdebugbar::studio.catalog';

        return view($view, [
            'groups' => $groups,
            'selected' => $selected,
            'selectedGroup' => $groups[$selected],
            'theme' => $theme,
            'previewWidth' => $previewWidth,
            'stylesheetUrl' => $this->assets->for('newdebugbar.css'),
            'scriptUrl' => $this->assets->for('newdebugbar.js'),
        ]);
    }
}
