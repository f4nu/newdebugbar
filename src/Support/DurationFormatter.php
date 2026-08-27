<?php

namespace NewDebugBar\Support;

/** Formats milliseconds with the clearest practical duration unit. */
final class DurationFormatter
{
    public static function format(mixed $milliseconds): string
    {
        if (! is_numeric($milliseconds)) {
            return '—';
        }

        $milliseconds = max(0.0, (float) $milliseconds);

        if (! is_finite($milliseconds)) {
            return '—';
        }

        if ($milliseconds >= 1_000) {
            return self::decimal($milliseconds / 1_000).' s';
        }

        if ($milliseconds >= 1) {
            return self::decimal($milliseconds).' ms';
        }

        if ($milliseconds === 0.0) {
            return '0 µs';
        }

        $microseconds = $milliseconds * 1_000;

        return $microseconds < 1
            ? '<1 µs'
            : number_format($microseconds, 0, '.', '').' µs';
    }

    private static function decimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
