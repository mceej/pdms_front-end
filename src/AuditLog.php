<?php

/**
 * The audit log, read and filtered.
 *
 * The table on screen and the CSV export ask the same questions of the same
 * branch, so the questions are asked here once rather than in both endpoints.
 *
 * The database can order a branch by one field, and that field is the time an
 * entry was written. Everything else — the module, the search, the dates — is
 * decided here, over the entries the database handed back.
 */
class AuditLog
{
    /**
     * How many entries are read for a page of the table.
     *
     * A log only grows, so there has to be a line somewhere. Past it the reply
     * says so rather than quietly leaving the oldest entries out.
     */
    public const MAX_ENTRIES = 5000;

    /**
     * How many are read for an export.
     *
     * Higher, because a download is asked for deliberately and is expected to
     * be complete — but not unbounded: the whole branch has to fit in memory,
     * and the container is given 128 MB.
     */
    public const MAX_EXPORT_ENTRIES = 25000;

    /** The heading of each column, in the order the table shows them. */
    public const HEADINGS = [
        'timestamp' => 'Timestamp',
        'name' => 'Name',
        'userType' => 'User Type',
        'module' => 'Module',
        'action' => 'Action',
        'activity' => 'Activity',
        'ipAddress' => 'IP Address',
    ];

    /** @var array<int, array<string, mixed>> newest first */
    private array $entries = [];

    /**
     * @param array<string, mixed> $auditLogs the auditLogs branch
     */
    public function __construct(array $auditLogs)
    {
        foreach ($auditLogs as $id => $entry) {
            if (is_array($entry)) {
                $this->entries[] = $this->readEntry((string) $id, $entry);
            }
        }

        usort($this->entries, static fn (array $one, array $two): int => $two['at'] <=> $one['at']);
    }

    /**
     * The entries to read, as a path FirebaseAdmin::readMany understands.
     *
     * A date range is handed to the database rather than applied here. The
     * branch is indexed on the time an entry was written, so asking for a
     * month means a month crosses the wire instead of the whole log — which
     * is the difference between an export that waits on a download and one
     * that does not.
     *
     * @param array{from?: ?int, to?: ?int} $filters
     */
    public static function pathFor(array $filters, int $limit): string
    {
        $path = 'auditLogs?orderBy=' . urlencode('"at"');

        if (($filters['from'] ?? null) !== null) {
            $path .= '&startAt=' . (int) $filters['from'];
        }

        if (($filters['to'] ?? null) !== null) {
            $path .= '&endAt=' . (int) $filters['to'];
        }

        return $path . '&limitToLast=' . $limit;
    }

    /**
     * Read the filters out of a query string.
     *
     * @param array<string, mixed> $query usually $_GET
     *
     * @return array{modules: array<int, string>, search: string, from: ?int, to: ?int}
     *
     * @throws InvalidArgumentException when a date is not a date
     */
    public static function filtersFrom(array $query): array
    {
        $from = self::dayBound($query['start_date'] ?? null, 'start_date', false);
        $to = self::dayBound($query['end_date'] ?? null, 'end_date', true);

        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException('The start date comes after the end date.');
        }

        return [
            'modules' => self::moduleNames($query['module'] ?? []),
            'search' => trim((string) ($query['search'] ?? '')),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * One entry as the row of a spreadsheet.
     *
     * @param array<string, mixed> $entry
     *
     * @return array<int, string>
     */
    public static function csvRow(array $entry): array
    {
        $at = (int) ($entry['at'] ?? 0);

        return array_map(self::csvCell(...), [
            // Named with its time zone, so a file read somewhere else still
            // says when something happened rather than roughly when.
            $at > 0 ? date('Y-m-d H:i:s T', intdiv($at, 1000)) : '',
            (string) ($entry['name'] ?? ''),
            (string) ($entry['userType'] ?? ''),
            (string) ($entry['module'] ?? ''),
            (string) ($entry['action'] ?? ''),
            (string) ($entry['activity'] ?? ''),
            (string) ($entry['ipAddress'] ?? ''),
        ]);
    }

    /**
     * Keep a spreadsheet from reading a cell as a formula.
     *
     * The activity column holds text the people being logged wrote: file
     * names, target names. Excel and Sheets run a cell that starts with =, +,
     * - or @, so a name chosen carefully would run on whoever opened the
     * export. The quote makes it text, and spreadsheets do not show it.
     */
    public static function csvCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
    }

    /**
     * How many entries were read, before any filtering.
     */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * Whether the cap was reached, so entries exist that were not read.
     */
    public function reachedCap(int $limit): bool
    {
        return count($this->entries) >= $limit;
    }

    /**
     * Every module that appears in the log.
     *
     * Built from the entries themselves, so the filter offers exactly the
     * modules that were recorded and never drifts from them.
     *
     * @return array<int, string>
     */
    public function modules(): array
    {
        $modules = [];

        foreach ($this->entries as $entry) {
            if ($entry['module'] !== '') {
                $modules[$entry['module']] = true;
            }
        }

        $names = array_keys($modules);
        sort($names);

        return $names;
    }

    /**
     * The entries a request asked for, newest first.
     *
     * @param array{modules?: array<int, string>, search?: string, from?: ?int, to?: ?int} $filters
     *
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $filters): array
    {
        $modules = $filters['modules'] ?? [];
        $search = strtolower(trim((string) ($filters['search'] ?? '')));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $matching = [];

        foreach ($this->entries as $entry) {
            // Cheapest test first, so a filtered export does the least work.
            if ($modules !== [] && ! in_array($entry['module'], $modules, true)) {
                continue;
            }

            // The dates were handed to the database, which answered within
            // them. This is the net under that, for an entry with no time.
            if (($from !== null && $entry['at'] < $from) || ($to !== null && $entry['at'] > $to)) {
                continue;
            }

            if ($search !== '' && ! str_contains(self::haystack($entry), $search)) {
                continue;
            }

            $matching[] = $entry;
        }

        return $matching;
    }

    /**
     * Say in words which filters were applied, for the log and for the reader.
     *
     * @param array{modules?: array<int, string>, search?: string, from?: ?int, to?: ?int} $filters
     */
    public static function describe(array $filters): string
    {
        $parts = [];

        if (($filters['modules'] ?? []) !== []) {
            $parts[] = 'module ' . implode(', ', $filters['modules']);
        }

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $parts[] = 'search "' . trim((string) $filters['search']) . '"';
        }

        if (($filters['from'] ?? null) !== null) {
            $parts[] = 'from ' . date('Y-m-d', intdiv((int) $filters['from'], 1000));
        }

        if (($filters['to'] ?? null) !== null) {
            $parts[] = 'to ' . date('Y-m-d', intdiv((int) $filters['to'], 1000));
        }

        return $parts === [] ? 'no filters' : implode('; ', $parts);
    }

    /**
     * Turn one stored entry into a row, with the text a search looks through.
     *
     * @param array<string, mixed> $entry
     *
     * @return array<string, mixed>
     */
    private function readEntry(string $id, array $entry): array
    {
        $row = [
            'id' => $id,
            'at' => (int) ($entry['at'] ?? 0),
            'uid' => (string) ($entry['uid'] ?? ''),
            'name' => (string) ($entry['name'] ?? ''),
            'userType' => (string) ($entry['userType'] ?? ''),
            'module' => (string) ($entry['module'] ?? ''),
            'action' => (string) ($entry['action'] ?? ''),
            'activity' => (string) ($entry['activity'] ?? ''),
            'ipAddress' => (string) ($entry['ipAddress'] ?? ''),
        ];

        return $row;
    }

    /**
     * The text a search looks through, for one entry.
     *
     * Built during the one pass that needs it rather than stored on every
     * entry: a stored copy is roughly another entry's worth of memory each,
     * and an export of twenty thousand has to fit in the container.
     *
     * @param array<string, mixed> $entry
     */
    private static function haystack(array $entry): string
    {
        return strtolower(
            $entry['name'] . ' ' . $entry['userType'] . ' ' . $entry['module'] . ' '
            . $entry['action'] . ' ' . $entry['activity'] . ' ' . $entry['ipAddress']
        );
    }

    /**
     * The module names a request asked for, however it listed them.
     *
     * @return array<int, string>
     */
    private static function moduleNames(mixed $value): array
    {
        // A link carries them separated by commas; a form sends module[] twice.
        $names = is_string($value) ? explode(',', $value) : $value;

        if (! is_array($names)) {
            return [];
        }

        $trimmed = array_map(static fn (mixed $name): string => trim((string) $name), $names);

        return array_values(array_filter($trimmed, static fn (string $name): bool => $name !== ''));
    }

    /**
     * A YYYY-MM-DD parameter as milliseconds: the start of that day, or its end.
     *
     * @throws InvalidArgumentException
     */
    private static function dayBound(mixed $value, string $name, bool $endOfDay): ?int
    {
        $date = trim((string) ($value ?? ''));

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new InvalidArgumentException($name . ' must look like 2026-09-15.');
        }

        $moment = strtotime($date . ($endOfDay ? ' 23:59:59' : ' 00:00:00'));

        if ($moment === false) {
            throw new InvalidArgumentException($name . ' is not a real date.');
        }

        return $moment * 1000 + ($endOfDay ? 999 : 0);
    }
}
