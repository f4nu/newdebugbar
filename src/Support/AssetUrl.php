<?php

namespace NewDebugBar\Support;

use RuntimeException;

/** Builds cache-busted URLs for compiled package assets. */
final class AssetUrl
{
    public function for(string $asset): string
    {
        $root = realpath(__DIR__.'/../../dist');
        $path = realpath(__DIR__.'/../../dist/'.$asset);

        if ($root === false || $path === false || ! is_file($path) || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("New Debug Bar asset [{$asset}] is missing. Run npm run build in the package.");
        }

        return url('/__newdebugbar/assets/'.$asset).'?id='.substr(hash_file('sha256', $path), 0, 12);
    }
}
