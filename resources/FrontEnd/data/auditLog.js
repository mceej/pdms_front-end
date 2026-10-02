// The audit log. Entries are written by the server so that the address and the
// person's identity come from the request, and so nobody can rewrite their own
// history. Only an admin may read them.
import { limitToLast, onValue, query, orderByChild, ref as databaseRef } from 'firebase/database';
import { auth, database } from '../firebase/app.js';

const ENDPOINT = '/api/audit.php';

/**
 * Watch the most recent entries, newest first.
 *
 * @return {function} call it to stop watching
 */
export const subscribeAuditLog = (onChange, howMany = 500) => {
    if (database === null) {
        onChange([]);

        return () => {};
    }

    const recent = query(databaseRef(database, 'auditLogs'), orderByChild('at'), limitToLast(howMany));

    return onValue(
        recent,
        (snapshot) => {
            const value = snapshot.val() || {};
            const list = Object.entries(value).map(([id, entry]) => ({ id, ...entry }));

            onChange(list.sort((one, two) => (two.at || 0) - (one.at || 0)));
        },
        () => onChange([])
    );
};

/**
 * Record something the signed-in person did. Failures are ignored on purpose:
 * losing a log entry must never block the work itself.
 */
export const recordActivity = async (module, action, activity) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return;
    }

    try {
        const idToken = await signedInUser.getIdToken();

        await fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idToken, module, action, activity }),
        });
    } catch {
        // Nothing to do.
    }
};
