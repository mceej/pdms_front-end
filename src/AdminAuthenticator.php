<?php

/**
 * Checks a username and password against the configured admin account.
 */
class AdminAuthenticator
{
    private string $username;

    private string $passwordHash;

    /**
     * @param array{username: string, passwordHash: string} $account
     */
    public function __construct(array $account)
    {
        $this->username = $account['username'];
        $this->passwordHash = $account['passwordHash'];
    }

    /**
     * Report whether the given credentials belong to the admin account.
     */
    public function matches(string $username, string $password): bool
    {
        $usernameMatches = hash_equals($this->username, $username);
        $passwordMatches = password_verify($password, $this->passwordHash);

        return $usernameMatches && $passwordMatches;
    }
}
