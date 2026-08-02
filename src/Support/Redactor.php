<?php

namespace NewDebugBar\Support;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use UnitEnum;

/** Converts captured values into bounded, JSON-safe, redacted data. */
final class Redactor
{
    private const REDACTED = '[redacted]';

    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'authorization',
        'cookie',
        'set_cookie',
        'password',
        'password_confirmation',
        'secret',
        'session',
        'token',
        'access_token',
        'api_key',
        'client_secret',
        'csrf',
        '_token',
    ];

    public function __construct(
        private readonly int $maxDepth = 5,
        private readonly int $maxStringLength = 2_000,
        private readonly int $maxArrayItems = 100,
    ) {}

    public function clean(mixed $value, int $depth = 0, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitive($key)) {
            return self::REDACTED;
        }

        if ($depth >= $this->maxDepth) {
            return '[maximum depth reached]';
        }

        if (is_array($value)) {
            $clean = [];

            foreach (array_slice($value, 0, $this->maxArrayItems, true) as $itemKey => $item) {
                $clean[$itemKey] = $this->clean($item, $depth + 1, (string) $itemKey);
            }

            if (count($value) > $this->maxArrayItems) {
                $clean['__truncated__'] = count($value) - $this->maxArrayItems;
            }

            return $clean;
        }

        if (is_string($value)) {
            if (mb_strlen($value) <= $this->maxStringLength) {
                return $value;
            }

            return mb_substr($value, 0, $this->maxStringLength).'…';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Stringable) {
            return $this->clean((string) $value, $depth, $key);
        }

        if (is_object($value)) {
            return '['.$value::class.']';
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        return $value;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '.'], '_', $key));

        return in_array($normalized, self::SENSITIVE_KEYS, true)
            || str_ends_with($normalized, '_api_key')
            || str_ends_with($normalized, '_authorization')
            || str_ends_with($normalized, '_password')
            || str_ends_with($normalized, '_secret')
            || str_ends_with($normalized, '_token');
    }
}
