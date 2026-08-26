<?php

namespace NewDebugBar;

/** Adds bounded, redacted checkpoints to the active New Debug Bar profile. */
final class Debug
{
    /** @param array<string, mixed> $context */
    public static function message(string $label, array $context = []): void
    {
        app(ProfileManager::class)->message($label, $context);
    }
}
