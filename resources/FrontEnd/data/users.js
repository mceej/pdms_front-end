// The user list comes straight from the database; changes to accounts go
// through the server, because a browser cannot manage other people's logins.
import { onValue, ref as databaseRef } from 'firebase/database';
import { auth, database } from '../firebase/app.js';

const ENDPOINT = '/api/admin-users.php';

/**
 * Watch the user list. Calls back with an array whenever it changes.
 *
 * @return {function} call it to stop watching
 */
export const subscribeUsers = (onChange) => {
    if (database === null) {
        onChange([]);

        return () => {};
    }

    return onValue(
        databaseRef(database, 'users'),
        (snapshot) => {
            const value = snapshot.val() || {};

            onChange(Object.entries(value).map(([uid, profile]) => ({ id: uid, uid, ...profile })));
        },
        () => onChange([])
    );
};

/**
 * Ask the server to change an account.
 *
 * @return {Promise<object>} { ok: true, ... } or { ok: false, message }
 */
const askServer = async (action, payload = {}) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: 'Your session has expired. Sign in again.' };
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
        return { ok: false, message: 'Cannot reach the server. Check that it is running.' };
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
