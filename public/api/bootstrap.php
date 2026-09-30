<?php

/**
 * Shared setup for the JSON endpoints in this folder.
 */

require_once __DIR__ . '/../../src/FirebaseAdmin.php';

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
 * Make sure the request comes from a signed-in administrator.
 *
 * Returns the caller's account id, or stops with an error.
 *
 * @param array<string, mixed> $body
 */
function requireAdmin(FirebaseAdmin $admin, array $body): string
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

    return $accountId;
}
