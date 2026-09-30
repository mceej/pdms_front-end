<?php

/**
 * Account management for the admin user page.
 *
 * Creating a login, changing someone's password and blocking sign-in all need
 * admin rights that a browser cannot hold, so they happen here.
 */

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$body = jsonBody();

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 500);
}

$callerId = requireAdmin($admin, $body);
$action = (string) ($body['action'] ?? '');
$accountId = (string) ($body['uid'] ?? '');

/**
 * Collect the profile fields sent by the admin page.
 *
 * @param array<string, mixed> $body
 *
 * @return array<string, mixed>
 */
function profileFrom(array $body): array
{
    return [
        'name' => trim((string) ($body['name'] ?? '')),
        'email' => trim((string) ($body['email'] ?? '')),
        'section' => (string) ($body['section'] ?? ''),
        'role' => (string) ($body['role'] ?? ''),
        'status' => (string) ($body['status'] ?? 'Active'),
    ];
}

/**
 * Stop when a profile is missing something or holds an unknown value.
 *
 * @param array<string, mixed> $profile
 */
function requireValidProfile(array $profile): void
{
    $validSections = ['CIS', 'DRMD'];
    $validRoles = ['ADMIN', 'MANCOM', 'RDV Focal'];
    $validStatuses = ['Active', 'Inactive'];

    if ($profile['name'] === '' || $profile['email'] === '') {
        respond(['message' => 'Name and email are required.'], 422);
    }

    if (! in_array($profile['section'], $validSections, true)
        || ! in_array($profile['role'], $validRoles, true)
        || ! in_array($profile['status'], $validStatuses, true)) {
        respond(['message' => 'Section, role or status is not valid.'], 422);
    }
}

try {
    switch ($action) {
        case 'create':
            $profile = profileFrom($body);
            requireValidProfile($profile);
            $password = (string) ($body['password'] ?? '');

            if (strlen($password) < 6) {
                respond(['message' => 'The password must be at least 6 characters.'], 422);
            }

            $newId = $admin->createAccount($profile['email'], $password, $profile['name']);
            $profile['createdAt'] = (int) (microtime(true) * 1000);
            $admin->write('users/' . $newId, $profile);

            if ($profile['status'] === 'Inactive') {
                $admin->setDisabled($newId, true);
            }

            respond(['uid' => $newId, 'user' => $profile], 201);

            // no break

        case 'update':
            if ($accountId === '') {
                respond(['message' => 'Which account should change?'], 422);
            }

            $existing = $admin->read('users/' . $accountId);

            if (! is_array($existing)) {
                respond(['message' => 'That account no longer exists.'], 404);
            }

            $profile = profileFrom($body + ['email' => $existing['email'] ?? '']);
            requireValidProfile($profile);

            if ($accountId === $callerId && ($profile['role'] !== 'ADMIN' || $profile['status'] !== 'Active')) {
                respond(['message' => 'You cannot remove your own administrator access.'], 409);
            }

            $profile['createdAt'] = $existing['createdAt'] ?? (int) (microtime(true) * 1000);
            $profile['updatedAt'] = (int) (microtime(true) * 1000);
            $admin->write('users/' . $accountId, $profile);
            $admin->setDisabled($accountId, $profile['status'] === 'Inactive');

            respond(['uid' => $accountId, 'user' => $profile]);

            // no break

        case 'setPassword':
            if ($accountId === '') {
                respond(['message' => 'Which account should change?'], 422);
            }

            $password = (string) ($body['password'] ?? '');

            if (strlen($password) < 6) {
                respond(['message' => 'The password must be at least 6 characters.'], 422);
            }

            $admin->setPassword($accountId, $password);

            respond(['uid' => $accountId]);

            // no break

        case 'delete':
            if ($accountId === '') {
                respond(['message' => 'Which account should be removed?'], 422);
            }

            if ($accountId === $callerId) {
                respond(['message' => 'You cannot delete your own account.'], 409);
            }

            $admin->deleteAccount($accountId);
            $admin->remove('users/' . $accountId);

            respond(['uid' => $accountId]);

            // no break

        default:
            respond(['message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 422);
}
