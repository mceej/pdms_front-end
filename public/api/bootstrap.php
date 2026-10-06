<?php

/**
 * Shared setup for the JSON endpoints in this folder.
 */

require_once __DIR__ . '/../../src/FirebaseAdmin.php';

// A stray warning printed into the body would corrupt the JSON and block the
// status code, so send errors to the log instead of the response.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

/**
 * Load the application configuration.
 *
 * @return array<string, mixed>
 */
function config(): array
{
    return require __DIR__ . '/../../config.php';
}

/**
 * Send a JSON response and stop.
 *
 * @param array<string, mixed> $data
 */
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Reject anything that is not the expected HTTP method.
 */
function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        respond(['message' => 'Method not allowed.'], 405);
    }
}

/**
 * Read the JSON body of the request.
 *
 * @return array<string, mixed>
 */
function jsonBody(): array
{
    $body = json_decode((string) file_get_contents('php://input'), true);

    return is_array($body) ? $body : [];
}

/**
 * Write one entry to the audit log.
 *
 * @param array<string, mixed> $profile the person's stored profile
 * @param array{module: string, action: string, activity: string} $entry
 */
function recordAudit(FirebaseAdmin $admin, string $accountId, array $profile, array $entry): void
{
    $admin->push('auditLogs', [
        'at' => (int) (microtime(true) * 1000),
        'uid' => $accountId,
        'name' => (string) ($profile['name'] ?? 'Unknown'),
        'userType' => trim(($profile['role'] ?? '') . ' ' . ($profile['section'] ?? '')),
        'module' => $entry['module'],
        'action' => $entry['action'],
        'activity' => $entry['activity'],
        'ipAddress' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    ]);
}

/**
 * The sign-in token on a request that has no body to carry it.
 *
 * A GET keeps its parameters in the address, and an address ends up in server
 * logs and browser history, so the token travels in the Authorization header
 * instead.
 */
function bearerToken(): string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp((string) $name, 'Authorization') === 0) {
                $header = (string) $value;

                break;
            }
        }
    }

    return preg_match('/^Bearer\s+(.+)$/i', trim($header), $found) === 1 ? trim($found[1]) : '';
}

/**
 * A cheap look at a sign-in token before the database is troubled with it.
 *
 * This settles nothing about who is calling. The signature is not checked, and
 * only Google's answer is ever trusted for that. It exists because verifying a
 * token now happens alongside the reads rather than before them, and without it
 * anybody could spend the database's bandwidth by sending rubbish.
 */
function looksLikeIdToken(string $idToken, string $projectId): bool
{
    $parts = explode('.', $idToken);

    if (count($parts) !== 3) {
        return false;
    }

    $claims = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true);

    if (! is_array($claims) || ($claims['aud'] ?? '') !== $projectId) {
        return false;
    }

    // A minute of slack, for clocks that disagree.
    return (int) ($claims['exp'] ?? 0) > time() - 60;
}

/**
 * Make sure the account behind a request still works.
 *
 * Every role is allowed to read the dashboard, so this asks whether the account
 * is active and nothing about the role.
 *
 * @return array<string, mixed>
 */
function requireActiveProfile(mixed $profile): array
{
    if (! is_array($profile)) {
        respond(['message' => 'That account has no profile.'], 403);
    }

    if (($profile['status'] ?? '') !== 'Active') {
        respond(['message' => 'This account is inactive. Ask an administrator.'], 403);
    }

    return $profile;
}

/**
 * Make sure the request comes from a signed-in administrator.
 *
 * Returns the caller's account id and profile, or stops with an error.
 *
 * @param array<string, mixed> $body
 *
 * @return array{id: string, profile: array<string, mixed>}
 */
function requireAdmin(FirebaseAdmin $admin, array $body): array
{
    $idToken = (string) ($body['idToken'] ?? '');

    if ($idToken === '') {
        respond(['message' => 'Sign in first.'], 401);
    }

    $accountId = $admin->accountIdForToken($idToken);

    if ($accountId === null) {
        respond(['message' => 'Your session has expired. Sign in again.'], 401);
    }

    $profile = $admin->read('users/' . $accountId);

    if (! is_array($profile) || ($profile['role'] ?? '') !== 'ADMIN' || ($profile['status'] ?? '') !== 'Active') {
        respond(['message' => 'Only an active administrator can do this.'], 403);
    }

    return ['id' => $accountId, 'profile' => $profile];
}
