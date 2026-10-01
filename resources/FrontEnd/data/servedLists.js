// Importing a served list. The file is sent to the server, which parses it,
// writes the payout records and records the import in the audit log.
import { limitToLast, onValue, orderByChild, query, ref as databaseRef } from 'firebase/database';
import { auth, database } from '../firebase/app.js';

const ENDPOINT = '/api/served-lists.php';

/**
 * Watch the imports that have already happened, newest first.
 *
 * @return {function} call it to stop watching
 */
export const subscribeServedLists = (onChange, howMany = 50) => {
    if (database === null) {
        onChange([]);

        return () => {};
    }

    const recent = query(databaseRef(database, 'servedLists'), orderByChild('importedAt'), limitToLast(howMany));

    return onValue(
        recent,
        (snapshot) => {
            const value = snapshot.val() || {};
            const list = Object.entries(value).map(([id, entry]) => ({ id, ...entry }));

            onChange(list.sort((one, two) => (two.importedAt || 0) - (one.importedAt || 0)));
        },
        () => onChange([])
    );
};

/**
 * Remove an import and every payout record that came from it.
 *
 * @return {Promise<object>} { ok: true, recordsDeleted } or { ok: false, message }
 */
export const deleteServedList = async (servedListId) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: 'Your session has expired. Sign in again.' };
    }

    const form = new FormData();

    form.append('idToken', await signedInUser.getIdToken());
    form.append('action', 'delete');
    form.append('servedListId', servedListId);

    let response;

    try {
        response = await fetch(ENDPOINT, { method: 'POST', body: form });
    } catch {
        return { ok: false, message: 'Cannot reach the server. Check that it is running.' };
    }

    const body = await response.json().catch(() => ({}));

    return response.ok
        ? { ok: true, ...body }
        : { ok: false, message: body.message || 'The import could not be deleted.' };
};

/**
 * Send a served-list file for one barangay.
 *
 * @param {object} details file, province, municipality, barangay, disasterType, program, replace
 *
 * @return {Promise<object>} { ok: true, rowsImported, ... } or { ok: false, message, canReplace }
 */
export const importServedList = async (details) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: 'Your session has expired. Sign in again.' };
    }

    const form = new FormData();

    form.append('idToken', await signedInUser.getIdToken());
    form.append('file', details.file);
    form.append('province', details.province || '');
    form.append('municipality', details.municipality || '');
    form.append('barangay', details.barangay || '');
    form.append('disasterType', details.disasterType || '');

    if (details.program) {
        form.append('program', details.program);
    }

    if (details.replace) {
        form.append('replace', '1');
    }

    let response;

    try {
        response = await fetch(ENDPOINT, { method: 'POST', body: form });
    } catch {
        return { ok: false, message: 'Cannot reach the server. Check that it is running.' };
    }

    const body = await response.json().catch(() => ({}));

    if (response.ok) {
        return { ok: true, ...body };
    }

    return {
        ok: false,
        message: body.message || 'The import did not work. Try again.',
        // 409 means the same file was imported here before; the person may replace it.
        canReplace: response.status === 409,
    };
};
