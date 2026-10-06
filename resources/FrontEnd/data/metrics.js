// The dashboard's figures, worked out by the server in one request.
//
// The browser used to read every payout record and group them here. It asks the
// server for the finished figures instead, so the page carries one response
// rather than the whole branch — and the browser no longer needs to be allowed
// to read payoutRecords at all.
import { limitToLast, onValue, orderByChild, query, ref as databaseRef } from 'firebase/database';
import { auth, database } from '../firebase/app.js';

const ENDPOINT = '/api/metrics.php';

/**
 * The server answers in the wording of the acceptance criteria; the components
 * read the wording they already use. This is where the two meet.
 */
const asRow = (row) => ({
    id: row.id,
    name: row.name,
    target: row.target,
    paid: row.paid,
    remaining: row.remaining,
    progress: row.progress,
    targetAmount: row.target_amount,
    amountDisbursed: row.amount_disbursed,
    balance: row.balance,
});

const asKpi = (kpi) => ({
    // Pesos the targets ask for, and people they ask for.
    totalBalance: kpi.total_balance,
    targetBeneficiaries: kpi.target_beneficiaries,
    // People paid, and pesos moved.
    totalPaid: kpi.total_paid,
    totalDisbursed: kpi.total_disbursed,
    // What is left of each: pesos still to move, people still to pay.
    unpaidBalance: kpi.unpaid_balance,
    unpaidBeneficiaries: kpi.unpaid_beneficiaries,
    progress: kpi.progress.percent,
    progressFraction: kpi.progress.fraction,
});

/**
 * Turn the filters the dashboard holds into the query the endpoint takes.
 */
const asQuery = (filters) => {
    const query = new URLSearchParams();
    const pairs = {
        group: filters.group,
        province_id: filters.provinceId,
        municipality_id: filters.municipalityId,
        barangay_id: filters.barangayId,
        program: filters.program ? String(filters.program).toLowerCase() : '',
        disaster_type: filters.disasterType,
        assistance_type: filters.assistanceType,
        payout_site: filters.payoutSite,
        start_date: filters.from,
        end_date: filters.to,
    };

    Object.entries(pairs).forEach(([name, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            query.set(name, String(value));
        }
    });

    return query.toString();
};

/**
 * Ask the server for the dashboard.
 *
 * @return {Promise<object>} { ok: true, kpi, rows, total, ... } or { ok: false, message }
 */
export const fetchDashboardMetrics = async (filters = {}) => {
    const signedInUser = auth?.currentUser;

    if (! signedInUser) {
        return { ok: false, message: 'Your session has expired. Sign in again.' };
    }

    const query = asQuery(filters);
    let response;

    try {
        const idToken = await signedInUser.getIdToken();

        response = await fetch(query === '' ? ENDPOINT : `${ENDPOINT}?${query}`, {
            headers: { Authorization: `Bearer ${idToken}` },
        });
    } catch {
        return { ok: false, message: 'Cannot reach the server. Check that it is running.' };
    }

    const body = await response.json().catch(() => ({}));

    if (! response.ok) {
        return { ok: false, message: body.message || 'Could not load the dashboard.' };
    }

    return {
        ok: true,
        kpi: asKpi(body.kpi),
        grouping: body.grouping,
        rows: (body.rows || []).map(asRow),
        total: asRow(body.total),
        payoutSites: body.payout_sites || [],
        disasterTypes: body.disaster_types || [],
        lastUpdated: body.last_updated,
        // Things the figures cannot say for themselves, such as targets that
        // were left out. Worth showing rather than swallowing.
        warnings: body.warnings || [],
    };
};

/**
 * Call back whenever the figures behind the dashboard change.
 *
 * Asking the server for finished figures costs the page its live updates: a
 * fetch answers once, where the old subscription to payoutRecords kept coming.
 * This puts them back without the download. Payout records only ever arrive
 * through an import and leave with one, so the imports are the signal; targets
 * move the denominators, so they are the other.
 *
 * Only the newest record of each is watched, which is a few hundred bytes
 * rather than the branch.
 *
 * @param {function} onChange called on every change after the first read
 *
 * @return {function} call it to stop watching
 */
export const subscribeToChanges = (onChange) => {
    if (database === null) {
        return () => {};
    }

    const watch = (path, sortBy) => {
        // The first call is the value as it already stands, which the page has
        // just fetched. Only what comes after it is news.
        let seenFirst = false;

        return onValue(
            query(databaseRef(database, path), orderByChild(sortBy), limitToLast(1)),
            () => {
                if (seenFirst) {
                    onChange();

                    return;
                }

                seenFirst = true;
            },
            () => {}
        );
    };

    const stop = [watch('servedLists', 'importedAt'), watch('targets', 'updatedAt')];

    return () => stop.forEach((unsubscribe) => unsubscribe());
};
