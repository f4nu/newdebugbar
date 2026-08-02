<?php

namespace NewDebugBar\Storage;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class ProfileStore
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private readonly int $maxProfiles = 20,
        private readonly int $maxAgeMinutes = 60,
    ) {}

    /** @param array<string, mixed> $profile */
    public function put(array $profile): string
    {
        $id = (string) ($profile['id'] ?? '');
        $this->assertValidId($id);
        $this->files->ensureDirectoryExists($this->path, 0700);

        try {
            $json = json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The debug profile could not be encoded.', previous: $exception);
        }

        $destination = $this->filename($id);
        $temporary = $destination.'.'.bin2hex(random_bytes(6)).'.tmp';

        if ($this->files->put($temporary, $json, true) === false) {
            throw new RuntimeException('The debug profile could not be written.');
        }

        @chmod($temporary, 0600);

        if (! @rename($temporary, $destination)) {
            $this->files->delete($temporary);

            throw new RuntimeException('The debug profile could not be stored atomically.');
        }

        $this->prune();

        return $id;
    }

    /** @return array<string, mixed>|null */
    public function get(string $id): ?array
    {
        $this->assertValidId($id);
        $filename = $this->filename($id);

        if (! $this->files->isFile($filename)) {
            return null;
        }

        $expiresAt = now()->subMinutes($this->maxAgeMinutes)->getTimestamp();

        if ($this->files->lastModified($filename) < $expiresAt) {
            $this->files->delete($filename);

            return null;
        }

        try {
            $profile = json_decode($this->files->get($filename), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($profile) ? $profile : null;
    }

    private function prune(): void
    {
        $files = collect($this->files->files($this->path))
            ->filter(fn ($file): bool => $file->getExtension() === 'json')
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->values();

        $expiresAt = now()->subMinutes($this->maxAgeMinutes)->getTimestamp();

        $files->each(function ($file, int $index) use ($expiresAt): void {
            if ($index >= $this->maxProfiles || $file->getMTime() < $expiresAt) {
                $this->files->delete($file->getPathname());
            }
        });
    }

    private function filename(string $id): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$id.'.json';
    }

    private function assertValidId(string $id): void
    {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $id) !== 1) {
            throw new InvalidArgumentException('Invalid debug profile ID.');
        }
    }
}
