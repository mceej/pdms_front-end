<?php

require_once __DIR__ . '/GoogleServiceAccount.php';
require_once __DIR__ . '/HttpJson.php';

/**
 * Manages Firebase accounts and database records with admin rights.
 *
 * Everything here runs on the server. The browser never sees the key.
 */
class FirebaseAdmin
{
    private const IDENTITY_URL = 'https://identitytoolkit.googleapis.com/v1/projects/';

    private GoogleServiceAccount $account;

    private string $projectId;

    private string $databaseUrl;

    /**
     * @param array{projectId: string, databaseUrl: string, serviceAccountPath: string} $settings
     */
    public function __construct(array $settings)
    {
        $this->account = new GoogleServiceAccount($settings['serviceAccountPath']);
        $this->projectId = $settings['projectId'];
        $this->databaseUrl = rtrim($settings['databaseUrl'], '/');
    }

    /**
     * Return the account id behind a sign-in token, or null if it is not valid.
     */
    public function accountIdForToken(string $idToken): ?string
    {
        $response = $this->callIdentity(':lookup', ['idToken' => $idToken]);
        $localId = $response['body']['users'][0]['localId'] ?? null;

        return $response['status'] === 200 && is_string($localId) ? $localId : null;
    }

    /**
     * Create a login and return its account id.
     *
     * @throws RuntimeException when Firebase refuses, e.g. the email is taken
     */
    public function createAccount(string $email, string $password, string $displayName): string
    {
        $response = $this->callIdentity('', [
            'email' => $email,
            'password' => $password,
            'displayName' => $displayName,
        ]);

        if ($response['status'] !== 200 || ! isset($response['body']['localId'])) {
            throw new RuntimeException($this->messageFor($response));
        }

        return (string) $response['body']['localId'];
    }

    /**
     * Change an existing person's password.
     */
    public function setPassword(string $accountId, string $password): void
    {
        $response = $this->callIdentity(':update', ['localId' => $accountId, 'password' => $password]);

        if ($response['status'] !== 200) {
            throw new RuntimeException($this->messageFor($response));
        }
    }

    /**
     * Block or allow sign-in for an account.
     */
    public function setDisabled(string $accountId, bool $disabled): void
    {
        $response = $this->callIdentity(':update', ['localId' => $accountId, 'disableUser' => $disabled]);

        if ($response['status'] !== 200) {
            throw new RuntimeException($this->messageFor($response));
        }
    }

    /**
     * Remove a login completely.
     */
    public function deleteAccount(string $accountId): void
    {
        $response = $this->callIdentity(':delete', ['localId' => $accountId]);

        if ($response['status'] !== 200) {
            throw new RuntimeException($this->messageFor($response));
        }
    }

    /**
     * Read a path in the database, ignoring the security rules.
     */
    public function read(string $path): mixed
    {
        $response = $this->callDatabase('GET', $path);

        return $response['body'] ?: null;
    }

    /**
     * Write a path in the database, ignoring the security rules.
     *
     * @param array<string, mixed> $value
     */
    public function write(string $path, array $value): void
    {
        $response = $this->callDatabase('PUT', $path, $value);

        if ($response['status'] !== 200) {
            throw new RuntimeException('Could not save to the database.');
        }
    }

    /**
     * Add a record under a path and return its new key.
     *
     * @param array<string, mixed> $value
     */
    public function push(string $path, array $value): string
    {
        $response = $this->callDatabase('POST', $path, $value);

        if ($response['status'] !== 200) {
            throw new RuntimeException('Could not save to the database.');
        }

        return (string) ($response['body']['name'] ?? '');
    }

    /**
     * Write several records under a path in one request, leaving the rest alone.
     *
     * @param array<string, mixed> $values keyed by record id
     */
    public function patch(string $path, array $values): void
    {
        $response = $this->callDatabase('PATCH', $path, $values);

        if ($response['status'] !== 200) {
            throw new RuntimeException('Could not save to the database.');
        }
    }

    /**
     * Read the records under a path whose field holds a given value.
     *
     * @return array<string, mixed>
     */
    public function findBy(string $path, string $field, string $value): array
    {
        $query = sprintf('?orderBy=%s&equalTo=%s', urlencode('"' . $field . '"'), urlencode('"' . $value . '"'));
        $response = HttpJson::send(
            'GET',
            $this->databaseUrl . '/' . ltrim($path, '/') . '.json' . $query,
            [],
            ['Authorization: Bearer ' . $this->account->accessToken()]
        );

        return $response['status'] === 200 ? $response['body'] : [];
    }

    /**
     * Delete a path in the database, ignoring the security rules.
     */
    public function remove(string $path): void
    {
        $this->callDatabase('DELETE', $path);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function callIdentity(string $action, array $body): array
    {
        return HttpJson::send(
            'POST',
            self::IDENTITY_URL . $this->projectId . '/accounts' . $action,
            $body,
            ['Authorization: Bearer ' . $this->account->accessToken()]
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function callDatabase(string $method, string $path, array $body = []): array
    {
        return HttpJson::send(
            $method,
            $this->databaseUrl . '/' . ltrim($path, '/') . '.json',
            $body,
            ['Authorization: Bearer ' . $this->account->accessToken()]
        );
    }

    /**
     * Turn a Firebase error into something worth showing a person.
     *
     * @param array{status: int, body: array<string, mixed>} $response
     */
    private function messageFor(array $response): string
    {
        $reason = (string) ($response['body']['error']['message'] ?? 'UNKNOWN');

        return match (true) {
            str_contains($reason, 'EMAIL_EXISTS') => 'That email already has an account.',
            str_contains($reason, 'INVALID_EMAIL') => 'That email address is not valid.',
            str_contains($reason, 'WEAK_PASSWORD') => 'The password must be at least 6 characters.',
            str_contains($reason, 'USER_NOT_FOUND') => 'That account no longer exists.',
            default => 'Firebase refused the request: ' . $reason,
        };
    }
}
