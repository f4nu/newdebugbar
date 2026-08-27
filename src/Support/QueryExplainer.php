<?php

namespace NewDebugBar\Support;

use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Throwable;

/** Runs an explicit, read-only EXPLAIN for a safely retained query. */
final class QueryExplainer
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly Redactor $redactor,
    ) {}

    /** @param array<string, mixed> $query @return array<string, mixed> */
    public function explain(array $query): array
    {
        $environments = config('newdebugbar.environments', ['local']);

        if (! is_array($environments) || ! app()->environment($environments)) {
            throw new InvalidArgumentException('Manual EXPLAIN is not available in this environment.');
        }

        if (($query['source_preserved'] ?? false) !== true
            || ($query['binding_policy'] ?? null) !== 'full'
            || ($query['bindings_complete'] ?? false) !== true) {
            throw new InvalidArgumentException('Full, complete bindings are required.');
        }

        $sql = trim((string) ($query['sql'] ?? ''));

        if (! $this->isReadOnly($sql)) {
            throw new InvalidArgumentException('Only one read query can be explained.');
        }

        $connectionName = (string) ($query['connection'] ?? config('database.default'));

        try {
            $connection = $this->database->connection($connectionName);
            $driver = $connection->getDriverName();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'This query\'s database connection is unavailable. Restore it, then reload.',
                previous: $exception,
            );
        }
        $prefix = match ($driver) {
            'mysql' => 'EXPLAIN ',
            'pgsql' => 'EXPLAIN (FORMAT JSON) ',
            'sqlite' => 'EXPLAIN QUERY PLAN ',
            default => throw new InvalidArgumentException('This database driver is not supported.'),
        };
        $bindings = $query['bindings'] ?? [];

        if (! is_array($bindings)) {
            throw new InvalidArgumentException('Query bindings are not available.');
        }

        try {
            $results = $connection->select($prefix.$sql, $bindings);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                $this->failureMessage($exception, $driver),
                previous: $exception,
            );
        }

        $rows = array_map(fn (mixed $row): array => is_object($row) ? (array) $row : (array) $row, $results);
        $safeRows = $this->redactor->clean($rows);

        return [
            'driver' => $driver,
            'mode' => $driver === 'sqlite' ? 'EXPLAIN QUERY PLAN' : 'EXPLAIN',
            'rows' => is_array($safeRows) ? $safeRows : [],
        ];
    }

    private function isReadOnly(string $sql): bool
    {
        if ($sql === '' || str_contains($sql, ';')) {
            return false;
        }

        if (preg_match('/\b(for\s+update|lock\s+in\s+share|into\s+(?:out|dump)file)\b/i', $sql) === 1) {
            return false;
        }

        preg_match('/^(?:\/\*.*?\*\/\s*)*([a-z]+)/is', $sql, $matches);
        $verb = strtolower($matches[1] ?? '');

        if ($verb === 'select') {
            return true;
        }

        return $verb === 'with'
            && preg_match('/\b(insert|update|delete|merge|replace|truncate|drop|alter|create)\b/i', $sql) !== 1;
    }

    private function failureMessage(Throwable $exception, string $driver): string
    {
        while ($exception->getPrevious() instanceof Throwable) {
            $exception = $exception->getPrevious();
        }

        $reason = strtolower($exception->getMessage());

        if ($driver === 'sqlite' && str_contains($reason, 'no such function:')) {
            return 'SQLite cannot find a function used by this query. Check its name or register it on the query connection, then reload.';
        }

        if ($driver === 'sqlite' && str_contains($reason, 'no such table:')) {
            return 'SQLite cannot find a table used by this query. Check the database and confirm the table still exists.';
        }

        return 'Copy the query from Overview, then run EXPLAIN in your database client against the same database.';
    }
}
