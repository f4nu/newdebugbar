<?php

namespace NewDebugBar\Collectors;

/** Keeps bounded normalized cause stacks and source lines structured after redaction. */
final class ExceptionCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'exceptions';
    }

    public function label(): string
    {
        return 'Exceptions';
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    protected function cleanItem(array $item): array
    {
        $causes = array_values(array_filter(
            is_array($item['causes'] ?? null) ? $item['causes'] : [],
            'is_array',
        ));
        unset($item['causes']);

        /** @var array<string, mixed> $clean */
        $clean = $this->redactor->clean($item);
        $clean['causes'] = array_map(function (array $cause): array {
            /** @var array<string, mixed> $cleanCause */
            $cleanCause = $this->redactor->clean($cause);

            return $cleanCause;
        }, $causes);

        return $clean;
    }
}
