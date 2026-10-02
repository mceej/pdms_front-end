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

$caller = requireAdmin($admin, $body);
$callerId = $caller['id'];
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
    $profile = [
        'name' => trim((string) ($body['name'] ?? '')),
        'email' => trim((string) ($body['email'] ?? '')),
        'role' => (string) ($body['role'] ?? ''),
        'status' => (string) ($body['status'] ?? 'Active'),
    ];

    $section = trim((string) ($body['section'] ?? ''));

    // Only an RDV Focal belongs to a section, so the field is left out for the
    // other roles rather than stored empty.
    if ($section !== '') {
        $profile['section'] = $section;
    }

    return $profile;
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

    if (! in_array($profile['role'], $validRoles, true)
        || ! in_array($profile['status'], $validStatuses, true)) {
        respond(['message' => 'That role or status is not valid.'], 422);
    }

    if (isset($profile['section']) && ! in_array($profile['section'], $validSections, true)) {
        respond(['message' => 'That section is not valid.'], 422);
    }

    if ($profile['role'] === 'RDV Focal' && ! isset($profile['section'])) {
        respond(['message' => 'An RDV Focal needs an assigned section.'], 422);
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

            recordAudit($admin, $callerId, $caller['profile'], [
                'module' => 'User Management',
                'action' => 'Add User',
                'activity' => 'Created the account for ' . $profile['email'],
            ]);

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

            recordAudit($admin, $callerId, $caller['profile'], [
                'module' => 'User Management',
                'action' => 'Edit User',
                'activity' => sprintf(
                    'Updated %s (%s, %s, %s)',
                    $profile['name'],
                    $profile['section'],
                    $profile['role'],
                    $profile['status']
                ),
            ]);

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
            recordAudit($admin, $callerId, $caller['profile'], [
                'module' => 'User Management',
                'action' => 'Reset Password',
                'activity' => 'Set a new password for account ' . $accountId,
            ]);

            respond(['uid' => $accountId]);

            // no break

        case 'delete':
            if ($accountId === '') {
                respond(['message' => 'Which account should be removed?'], 422);
            }

            if ($accountId === $callerId) {
                respond(['message' => 'You cannot delete your own account.'], 409);
            }

            $removed = $admin->read('users/' . $accountId);
            $admin->deleteAccount($accountId);
            $admin->remove('users/' . $accountId);
            recordAudit($admin, $callerId, $caller['profile'], [
                'module' => 'User Management',
                'action' => 'Delete User',
                'activity' => 'Removed the account for '
                    . (is_array($removed) ? ($removed['email'] ?? $accountId) : $accountId),
            ]);

            respond(['uid' => $accountId]);

            // no break

        default:
            respond(['message' => 'Unknown action.'], 400);
    }
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 422);
}
