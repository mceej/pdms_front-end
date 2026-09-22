// Mock replacement for the old GET /api/payout-dashboard endpoint.
// Replace this module with real HTTP calls once the PHP backend exists.
import payoutData from './payoutData.json';

const { geographies, records } = payoutData;

const geographyById = new Map(geographies.map((geography) => [geography.id, geography]));

const levelFor = (filters) => {
    if (filters.barangay_id) {
        return 'detail';
    }
    if (filters.municipality_id) {
        return 'barangay';
    }
    if (filters.province_id) {
        return 'municipality';
    }

    return 'province';
};

const groupFieldFor = (level) => {
    switch (level) {
        case 'municipality':
            return 'municipality_id';
        case 'barangay':
        case 'detail':
            return 'barangay_id';
        default:
            return 'province_id';
    }
};

const matchesFilters = (record, filters) => {
    const fields = ['program', 'disaster_type', 'province_id', 'municipality_id', 'barangay_id', 'payout_site'];

    const matchesFields = fields.every((field) => {
        const value = filters[field];

        return value === undefined || value === null || value === '' || String(record[field]) === String(value);
    });

    if (!matchesFields) {
        return false;
    }

    if (filters.from && record.served_date < filters.from) {
        return false;
    }

    return !(filters.to && record.served_date > filters.to);
};

const summarise = (rows) => {
    const paid = rows.filter((row) => row.is_paid);
    const targetAmount = rows.reduce((total, row) => total + row.target_amount, 0);
    const amountDisbursed = rows.reduce((total, row) => total + row.disbursed_amount, 0);

    return {
        target: rows.length,
        paid: paid.length,
        remaining: Math.max(0, rows.length - paid.length),
        target_amount: targetAmount,
        amount_disbursed: amountDisbursed,
        unpaid_amount: Math.max(0, targetAmount - amountDisbursed),
        progress: rows.length ? Number(((paid.length / rows.length) * 100).toFixed(2)) : 0,
    };
};

const groupRows = (rows, groupField) => {
    const groups = new Map();

    rows.forEach((row) => {
        const id = row[groupField];
        if (!groups.has(id)) {
            groups.set(id, []);
        }
        groups.get(id).push(row);
    });

    return [...groups].map(([id, groupRows]) => {
        const geography = geographyById.get(id);
        const summary = summarise(groupRows);

        return {
            id,
            name: geography?.name ?? 'Unknown geography',
            psgc_code: geography?.psgc_code ?? null,
            target: summary.target,
            paid: summary.paid,
            remaining: summary.remaining,
            progress: summary.progress,
            amount_disbursed: summary.amount_disbursed,
        };
    });
};

export const fetchPayoutDashboard = async (filters = {}) => {
    const rows = records.filter((record) => matchesFilters(record, filters));
    const level = levelFor(filters);

    const disasterTypes = [...new Set(
        records
            .filter((record) => !filters.program || record.program === filters.program)
            .map((record) => record.disaster_type)
    )].sort();

    return {
        filters,
        level,
        summary: summarise(rows),
        rows: groupRows(rows, groupFieldFor(level)),
        payout_sites: [...new Set(rows.map((row) => row.payout_site).filter(Boolean))],
        disaster_types: disasterTypes,
    };
};
