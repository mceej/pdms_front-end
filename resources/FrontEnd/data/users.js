// Accounts. Both the list and the changes go through the server: a browser
// cannot manage other people's logins, and now it cannot read the staff list
// either — only the one account it is signed in as.
import { auth } from '../firebase/app.js';

const ENDPOINT = '/api/admin-users.php';

const EXPIRED = 'Your session has expired. Sign in again.';
const UNREACHABLE = 'Cannot reach the server. Check that it is running.';

/**
 * Ask the server for the user list.
 *
 * @return {Promise<object>} { ok: true, users } or { ok: false, message }
 */
export const fetchUsers = async () => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: EXPIRED };
    }

    let response;

    try {
        const idToken = await signedInUser.getIdToken();

        response = await fetch(ENDPOINT, { headers: { Authorization: `Bearer ${idToken}` } });
    } catch {
        return { ok: false, message: UNREACHABLE };
    }

    const body = await response.json().catch(() => ({}));

    return response.ok
        ? { ok: true, users: body.users || [] }
        : { ok: false, message: body.message || 'Could not load the user list.' };
};

/**
 * Ask the server to change an account.
 *
 * @return {Promise<object>} { ok: true, ... } or { ok: false, message }
 */
const askServer = async (action, payload = {}) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: EXPIRED };
    }

    let response;

    try {
        const idToken = await signedInUser.getIdToken();

        response = await fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idToken, action, ...payload }),
        });
    } catch {
        return { ok: false, message: UNREACHABLE };
    }

    const body = await response.json().catch(() => ({}));

    return response.ok
        ? { ok: true, ...body }
        : { ok: false, message: body.message || 'That did not work. Try again.' };
};

export const createUser = (user) => askServer('create', user);

export const updateUser = (uid, changes) => askServer('update', { uid, ...changes });

export const setUserPassword = (uid, password) => askServer('setPassword', { uid, password });

export const deleteUser = (uid) => askServer('delete', { uid });
