<?php

namespace NewDebugBar;

/** Narrow application API for bounded, redacted developer messages. */
final class Debug
{
    /** @param array<string, mixed> $context */
    public static function message(string $label, array $context = []): void
    {
        app(ProfileManager::class)->message($label, $context);
    }
}
