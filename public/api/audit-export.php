<?php

/**
 * The audit log as a CSV file.
 *
 * It takes the same filters as the table, so what downloads is what the person
 * is looking at — every matching entry, not only the page on screen.
 *
 *   GET /api/audit-export.php?start_date=2026-09-01&end_date=2026-09-30
 *       Authorization: Bearer <Firebase ID token>
 *
 * Parameters, all optional:
 *   start_date, end_date      YYYY-MM-DD, against the time an entry was written
 *   module                    one name, repeated as module[], or comma-separated
 *   search                    matched against every column
 *
 * Three things keep it quick. The dates are handed to the database, so a month
 * means a month crosses the wire rather than the whole log. The rows are
 * written out and flushed as they are built, so the file starts arriving
 * before it is finished. And the entry recording the export is written after
 * the last byte has gone, so nobody waits on it.
 *
 * Taking a copy of the log out of the system is recorded, because a record of
 * who did what is worth little if it can be lifted unnoticed.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/AuditLog.php';

/** How many rows go out between flushes. */
const FLUSH_EVERY = 500;

requireMethod('GET');

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    cannotContinue($exception);
}

/* Read before the caller is checked, because the dates decide what to ask the
   database for, and that request goes out alongside the check. A bad date is
   answered after it, so somebody who is not an administrator learns nothing. */
$filters = ['modules' => [], 'search' => '', 'from' => null, 'to' => null];
$badFilter = null;

try {
    $filters = AuditLog::filtersFrom($_GET);
} catch (InvalidArgumentException $exception) {
    $badFilter = $exception->getMessage();
}

$caller = requireAdminReading($admin, [
    'auditLogs' => AuditLog::pathFor($filters, AuditLog::MAX_EXPORT_ENTRIES),
]);

if ($badFilter !== null) {
    respond(['message' => $badFilter], 422);
}

$log = new AuditLog($caller['values']['auditLogs'] ?? []);

// The branch is held twice until this goes: once as it arrived, once as rows.
// On a long export that is the difference between fitting in memory and not.
unset($caller['values']);

$rows = $log->filter($filters);

// Everything that could fail has been done, so from here the reply is a file
// and the JSON content type bootstrap set is replaced.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . exportFileName($filters) . '"');
// Somebody's activity history has no business sitting in a cache.
header('Cache-Control: no-store');

// A CSV of audit entries is mostly the same module and action names over and
// over, so it compresses to a fraction of its size. Over a slow connection
// that is most of the wait.
if (str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')), 'gzip')) {
    ob_start('ob_gzhandler');
}

$handle = fopen('php://output', 'wb');

// Without this Excel reads the file as the machine's own code page, and
// Philippine place names do not survive that.
fwrite($handle, "\xEF\xBB\xBF");

fputcsv($handle, array_values(AuditLog::HEADINGS), ',', '"', '');

$written = 0;

foreach ($rows as $entry) {
    fputcsv($handle, AuditLog::csvRow($entry), ',', '"', '');
    $written++;

    // Sent on its way rather than held until the last row is built.
    if ($written % FLUSH_EVERY === 0) {
        sendWhatIsReady();
    }
}

fclose($handle);
sendWhatIsReady();

/* The file has gone. Recording the export costs a round trip to the database,
   and there is no reason for the person downloading to wait through it — but
   it must happen even if they have closed the tab, which is what the call at
   the top of this block is for. */
ignore_user_abort(true);

recordAudit($admin, $caller['id'], $caller['profile'], [
    'module' => 'Audit Log',
    'action' => 'Export',
    'activity' => sprintf('Exported %d entries as CSV (%s)', $written, AuditLog::describe($filters)),
]);

/**
 * Push whatever has been written to the person waiting for it.
 */
function sendWhatIsReady(): void
{
    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
}

/**
 * Name the file after the range it covers, so a folder of them stays legible.
 *
 * @param array{from?: ?int, to?: ?int} $filters
 */
function exportFileName(array $filters): string
{
    $day = static fn (int $milliseconds): string => date('Y-m-d', intdiv($milliseconds, 1000));
    $from = $filters['from'] ?? null;
    $to = $filters['to'] ?? null;

    if ($from !== null && $to !== null) {
        return $day($from) === $day($to)
            ? 'audit-log-' . $day($from) . '.csv'
            : 'audit-log-' . $day($from) . '-to-' . $day($to) . '.csv';
    }

    if ($from !== null) {
        return 'audit-log-from-' . $day($from) . '.csv';
    }

    if ($to !== null) {
        return 'audit-log-to-' . $day($to) . '.csv';
    }

    return 'audit-log-' . date('Y-m-d') . '.csv';
}
