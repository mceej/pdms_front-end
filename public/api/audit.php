<?php

/**
 * Records one audit entry.
 *
 * Entries are written here rather than from the browser so that the address
 * and the person's identity come from the request itself, and so that nobody
 * can rewrite the history of what they did.
 */

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$body = jsonBody();

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 500);
}

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
