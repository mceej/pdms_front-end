// The audit log. Entries are written by the server so that the address and the
// person's identity come from the request, and so nobody can rewrite their own
// history. They are read through the server too, so the browser needs no
// permission to read the log and carries one page instead of the whole branch.
import { auth } from '../firebase/app.js';

const ENDPOINT = '/api/audit.php';
const EXPORT_ENDPOINT = '/api/audit-export.php';

const EXPIRED = 'Your session has expired. Sign in again.';
const UNREACHABLE = 'Cannot reach the server. Check that it is running.';

/**
 * Turn what the page is showing into the query the endpoints take.
 *
 * Both the table and the export are asked the same question, so they are asked
 * it the same way and the file matches what is on screen.
 */
const asQuery = ({ modules = [], search = '', from = '', to = '', limit, offset } = {}) => {
    const query = new URLSearchParams();

    modules.filter(Boolean).forEach((module) => query.append('module[]', module));

    const pairs = { search, start_date: from, end_date: to, limit, offset };

    Object.entries(pairs).forEach(([name, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            query.set(name, String(value));
        }
    });

    return query.toString();
};

/**
 * Ask the server for one page of the log.
 *
 * @return {Promise<object>} { ok: true, entries, total, modules } or { ok: false, message }
 */
export const fetchAuditLog = async (options = {}) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: EXPIRED };
    }

    const query = asQuery(options);
    let response;

    try {
        const idToken = await signedInUser.getIdToken();

        response = await fetch(query === '' ? ENDPOINT : `${ENDPOINT}?${query}`, {
            headers: { Authorization: `Bearer ${idToken}` },
        });
    } catch {
        return { ok: false, message: UNREACHABLE };
    }

    const body = await response.json().catch(() => ({}));

    if (! response.ok) {
        return { ok: false, message: body.message || 'Could not load the audit log.' };
    }

    return {
        ok: true,
        entries: body.entries || [],
        total: body.total || 0,
        modules: body.modules || [],
        warnings: body.warnings || [],
    };
};

/**
 * Hand the browser a file to save.
 *
 * The token travels in a header, so the file cannot be fetched by pointing a
 * link at the endpoint; it is downloaded here and handed over as a blob.
 */
const saveFile = (blob, fileName) => {
    const address = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = address;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(address);
};

/**
 * Download the matching entries as a CSV file.
 *
 * @return {Promise<object>} { ok: true, fileName } or { ok: false, message }
 */
export const exportAuditLog = async (options = {}) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: EXPIRED };
    }

    // Every matching entry belongs in the file, not just the page on screen.
    const { limit, offset, ...filters } = options;
    const query = asQuery(filters);
    let response;

    try {
        const idToken = await signedInUser.getIdToken();

        response = await fetch(query === '' ? EXPORT_ENDPOINT : `${EXPORT_ENDPOINT}?${query}`, {
            headers: { Authorization: `Bearer ${idToken}` },
        });
    } catch {
        return { ok: false, message: UNREACHABLE };
    }

    if (! response.ok) {
        const body = await response.json().catch(() => ({}));

        return { ok: false, message: body.message || 'The export did not work. Try again.' };
    }

    const fileName = `audit-log-${new Date().toISOString().slice(0, 10)}.csv`;

    saveFile(await response.blob(), fileName);

    return { ok: true, fileName };
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
