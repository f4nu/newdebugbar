<?php

namespace NewDebugBar\Support;

use RuntimeException;

final class AssetUrl
{
    public function for(string $asset): string
    {
        $path = realpath(__DIR__.'/../../dist/'.$asset);

        if ($path === false || ! is_file($path)) {
            throw new RuntimeException("New Debug Bar asset [{$asset}] is missing. Run npm run build in the package.");
        }

        return url('/__new-debug-bar/assets/'.$asset).'?id='.substr(hash_file('sha256', $path), 0, 12);
    }
}
