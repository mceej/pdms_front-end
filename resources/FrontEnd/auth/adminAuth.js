// Talks to the PHP endpoints in public/api that handle the admin login.

const request = async (path, options = {}) => {
    const response = await fetch(path, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });

    const payload = await response.json().catch(() => ({}));

    return { ok: response.ok, payload };
};

/**
 * Sign in. Returns { ok } on success, or { ok: false, message } when rejected.
 */
export const signIn = async (username, password) => {
    try {
        const { ok, payload } = await request('/api/login.php', {
            method: 'POST',
            body: JSON.stringify({ username, password }),
        });

        return ok ? { ok } : { ok: false, message: payload.message || 'Unable to sign in.' };
    } catch {
        return { ok: false, message: 'Cannot reach the server. Check that it is running.' };
    }
};

/**
 * Sign out. Failures are ignored: the app returns to the login page either way.
 */
export const signOut = async () => {
    try {
        await request('/api/logout.php', { method: 'POST' });
    } catch {
        // Nothing to do.
    }
};

/**
 * Report whether this browser is already signed in.
 */
export const isSignedIn = async () => {
    try {
        const { ok, payload } = await request('/api/session.php');

        return ok && payload.signedIn === true;
    } catch {
        return false;
    }
};
