<?php

/**
 * The audit log: reading it, and adding to it.
 *
 * Entries are written here rather than from the browser so that the address
 * and the person's identity come from the request itself, and so that nobody
 * can rewrite the history of what they did.
 *
 * They are read here for the same reason the dashboard figures are: the page
 * asks for the one screenful it is showing instead of downloading the branch,
 * and the browser needs no permission to read the log at all.
 *
 *   GET  /api/audit.php?module=User+Management&search=carlo&limit=10&offset=0
 *        Authorization: Bearer <Firebase ID token>
 *   POST /api/audit.php    { idToken, module, action, activity }
 *
 * Parameters on the GET, all optional:
 *   module                    one name, repeated as module[], or comma-separated
 *   search                    matched against every column
 *   start_date, end_date      YYYY-MM-DD, against the time an entry was written
 *   limit, offset             one page of the matching entries
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/AuditLog.php';

/** A screenful at a time; more than this is what the export is for. */
const MAX_PAGE = 200;

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    cannotContinue($exception);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $caller = requireAdminReading($admin, ['auditLogs' => AuditLog::newestPath()]);

    try {
        $filters = AuditLog::filtersFrom($_GET);
    } catch (InvalidArgumentException $exception) {
        respond(['message' => $exception->getMessage()], 422);
    }

    $log = new AuditLog($caller['values']['auditLogs'] ?? []);
    $matching = $log->filter($filters);
    $limit = min(max((int) ($_GET['limit'] ?? 10), 1), MAX_PAGE);
    $offset = max((int) ($_GET['offset'] ?? 0), 0);

    respond([
        'entries' => array_map(AuditLog::asRow(...), array_slice($matching, $offset, $limit)),
        'total' => count($matching),
        'modules' => $log->modules(),
        'limit' => $limit,
        'offset' => $offset,
        // Said plainly rather than quietly dropping the oldest entries.
        'warnings' => $log->reachedCap()
            ? ['Only the newest ' . AuditLog::MAX_ENTRIES . ' entries were searched.']
            : [],
    ]);
}

requireMethod('POST');

$body = jsonBody();
$accountId = $admin->accountIdForToken((string) ($body['idToken'] ?? ''));

if ($accountId === null) {
    respond(['message' => 'Sign in first.'], 401);
}

$profile = $admin->read('users/' . $accountId);

if (! is_array($profile)) {
    respond(['message' => 'That account has no profile.'], 403);
}

recordAudit($admin, $accountId, $profile, [
    'module' => (string) ($body['module'] ?? ''),
    'action' => (string) ($body['action'] ?? ''),
    'activity' => (string) ($body['activity'] ?? ''),
]);

respond(['recorded' => true], 201);
