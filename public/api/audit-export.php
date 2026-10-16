<?php

/**
 * The audit log as a CSV file.
 *
 * It takes the same filters as the table, so what downloads is what the person
 * is looking at — every matching entry, not only the page on screen.
 *
 *   GET /api/audit-export.php?module=User+Management&start_date=2026-09-01
 *       Authorization: Bearer <Firebase ID token>
 *
 * Taking a copy of the log out of the system is itself recorded, because a
 * record of who did what is worth little if it can be lifted unnoticed.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/AuditLog.php';

requireMethod('GET');

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    cannotContinue($exception);
}

$caller = requireAdminReading($admin, ['auditLogs' => AuditLog::newestPath()]);

try {
    $filters = AuditLog::filtersFrom($_GET);
} catch (InvalidArgumentException $exception) {
    respond(['message' => $exception->getMessage()], 422);
}

$log = new AuditLog($caller['values']['auditLogs'] ?? []);
$rows = $log->filter($filters);

recordAudit($admin, $caller['id'], $caller['profile'], [
    'module' => 'Audit Log',
    'action' => 'Export',
    'activity' => sprintf('Exported %d entries as CSV (%s)', count($rows), AuditLog::describe($filters)),
]);

// Everything that could fail has been done. From here the reply is a file, so
// the JSON content type bootstrap set is replaced.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="audit-log-' . date('Y-m-d') . '.csv"');
// Somebody's activity history has no business sitting in a cache.
header('Cache-Control: no-store');

$handle = fopen('php://output', 'wb');

// Without this Excel reads the file as the machine's own code page, and
// Philippine place names do not survive that.
fwrite($handle, "\xEF\xBB\xBF");

fputcsv($handle, array_values(AuditLog::HEADINGS), ',', '"', '');

foreach ($rows as $entry) {
    fputcsv($handle, AuditLog::csvRow($entry), ',', '"', '');
}

fclose($handle);
