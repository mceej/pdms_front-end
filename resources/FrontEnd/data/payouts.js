// Payout figures for the dashboard, read from the database.
//
// It returns the same shape the mock module did, so the dashboard did not have
// to change. With a few thousand records this grouping is fine in the browser;
// beyond that, write the totals into summaries/ when data is imported and read
// those instead.
import { onValue, ref as databaseRef } from 'firebase/database';
import { database } from '../firebase/app.js';

let cachedRecords = null;
let pending = null;
let stopWatching = null;

/**
 * Keep every payout record in memory and follow changes to it.
 *
 * Reading once and watching means filtering never waits on the network, and
 * imported data shows up without a reload.
 *
 * @return {Promise<Array>}
 */
const loadRecords = () => {
    if (cachedRecords !== null) {
        return Promise.resolve(cachedRecords);
    }

    if (database === null) {
        return Promise.resolve([]);
    }

    if (pending !== null) {
        return pending;
    }

    pending = new Promise((resolve, reject) => {
        stopWatching = onValue(
            databaseRef(database, 'payoutRecords'),
            (snapshot) => {
                const value = snapshot.val() || {};

                cachedRecords = Object.entries(value).map(([id, record]) => ({ id, ...record }));
                resolve(cachedRecords);
            },
            (error) => {
                // Forget everything so the next attempt tries again, and let the
                // caller show the failure rather than treating it as "no records".
                forgetPayoutRecords();
                reject(error);
            }
        );
    });

    return pending;
};

/**
 * Drop what is held in memory and stop watching.
 *
 * Called when somebody signs in or out, so one person's data never carries
 * over to the next and a failed read is never remembered as "no records".
 */
export const forgetPayoutRecords = () => {
    if (stopWatching !== null) {
        stopWatching();
        stopWatching = null;
    }

    cachedRecords = null;
    pending = null;
};

const levelFor = (filters) => {
    if (filters.barangayId) {
        return 'detail';
    }

    if (filters.municipalityId) {
        return 'barangay';
    }

    return filters.provinceId ? 'municipality' : 'province';
};

const groupingFor = (level) => {
    switch (level) {
        case 'municipality':
            return ['municipalityId', 'municipalityName'];
        case 'barangay':
        case 'detail':
            return ['barangayId', 'barangayName'];
        default:
            return ['provinceId', 'provinceName'];
    }
};

const programFor = (record) => String(record.programType || record.program || '').toUpperCase();

const belongsToProgram = (record, program) => {
    const recordProgram = programFor(record);

    if (program === 'AICS') {
        return ['AICS', 'AKAP', 'UPLIFT', 'AICS-UPLIFT'].includes(recordProgram);
    }

    return program === 'ECT' ? recordProgram === 'ECT' : true;
};

const matchesFilters = (record, filters) => {
    const pairs = [
        ['disasterType', filters.disasterType],
        ['provinceId', filters.provinceId],
        ['municipalityId', filters.municipalityId],
        ['barangayId', filters.barangayId],
        ['payoutSite', filters.payoutSite],
    ];

    const recordProgram = programFor(record);

    if (! belongsToProgram(record, filters.program)) {
        return false;
    }

    if (filters.programType) {
        const wantedProgram = filters.programType.toUpperCase();
        const matchesProgramType = wantedProgram === 'UPLIFT'
            ? ['UPLIFT', 'AICS-UPLIFT'].includes(recordProgram)
            : recordProgram === wantedProgram;

        if (! matchesProgramType) {
            return false;
        }
    }

    // Records imported before assistance types were stored remain visible.
    if (filters.assistanceType && record.assistanceType
        && record.assistanceType !== filters.assistanceType) {
        return false;
    }

    const matches = pairs.every(([field, wanted]) => {
        return wanted === undefined || wanted === null || wanted === '' || String(record[field]) === String(wanted);
    });

    if (! matches) {
        return false;
    }

    if (filters.from && record.servedDate < filters.from) {
        return false;
    }

    return ! (filters.to && record.servedDate > filters.to);
};

const summarise = (rows) => {
    const paid = rows.filter((row) => row.isPaid);
    const targetAmount = rows.reduce((total, row) => total + Number(row.targetAmount || 0), 0);
    const amountDisbursed = rows.reduce((total, row) => total + Number(row.disbursedAmount || 0), 0);

    return {
        target: rows.length,
        paid: paid.length,
        remaining: Math.max(0, rows.length - paid.length),
        targetAmount: targetAmount,
        amountDisbursed: amountDisbursed,
        unpaidAmount: Math.max(0, targetAmount - amountDisbursed),
        progress: rows.length ? Number(((paid.length / rows.length) * 100).toFixed(2)) : 0,
    };
};

/**
 * The dashboard's figures for one set of filters.
 */
export const fetchPayoutDashboard = async (filters = {}) => {
    const records = await loadRecords();
    const rows = records.filter((record) => matchesFilters(record, filters));
    const level = levelFor(filters);
    const [idField, nameField] = groupingFor(level);
    const groups = new Map();

    rows.forEach((row) => {
        const id = row[idField];

        if (! groups.has(id)) {
            groups.set(id, { name: row[nameField], rows: [] });
        }

        groups.get(id).rows.push(row);
    });

    const disasterTypes = [...new Set(
        records
            .filter((record) => belongsToProgram(record, filters.program))
            .map((record) => record.disasterType)
            .filter(Boolean)
    )].sort();

    return {
        filters,
        level,
        summary: summarise(rows),
        rows: [...groups].map(([id, group]) => {
            const summary = summarise(group.rows);

            return {
                id,
                name: group.name || 'Unknown geography',
                target: summary.target,
                paid: summary.paid,
                remaining: summary.remaining,
                progress: summary.progress,
                amountDisbursed: summary.amountDisbursed,
            };
        }),
        payoutSites: [...new Set(rows.map((row) => row.payoutSite).filter(Boolean))],
        disasterTypes: disasterTypes,
    };
};
