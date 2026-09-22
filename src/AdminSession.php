<?php

/**
 * Remembers across requests that the admin signed in.
 */
class AdminSession
{
    private const KEY = 'isAdmin';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Report whether the current visitor is signed in.
     */
    public function isSignedIn(): bool
    {
        return ($_SESSION[self::KEY] ?? false) === true;
    }

    /**
     * Mark the current visitor as signed in.
     */
    public function signIn(): void
    {
        session_regenerate_id(true);
        $_SESSION[self::KEY] = true;
    }

    /**
     * Forget the current visitor.
     */
    public function signOut(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
