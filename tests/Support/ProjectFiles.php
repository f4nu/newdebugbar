<?php

namespace NewDebugBar\Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ProjectFiles
{
    /** @return iterable<SplFileInfo> */
    public static function bladeFilesIn(string $directory): iterable
    {
        foreach (self::filesIn($directory) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                yield $file;
            }
        }
    }

    /** @return RecursiveIteratorIterator<RecursiveDirectoryIterator> */
    public static function filesIn(string $directory): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );
    }

    public static function relativePath(SplFileInfo $file, string $directory): string
    {
        return ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
    }
}
