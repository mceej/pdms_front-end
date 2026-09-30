// Payout targets, read and written straight from the database.
// The security rules decide who may write which section.
import { equalTo, orderByChild, push, query, ref as databaseRef, set, update } from 'firebase/database';
import { onValue } from 'firebase/database';
import { auth, database } from '../firebase/app.js';

/**
 * Watch the targets of one section ('CIS' or 'DRMD').
 *
 * @return {function} call it to stop watching
 */
export const subscribeTargets = (section, onChange) => {
    if (database === null) {
        onChange([]);

        return () => {};
    }

    const sectionTargets = query(databaseRef(database, 'targets'), orderByChild('section'), equalTo(section));

    return onValue(
        sectionTargets,
        (snapshot) => {
            const value = snapshot.val() || {};
            const list = Object.entries(value).map(([id, target]) => ({ id, ...target }));

            onChange(list.sort((one, two) => (two.createdAt || 0) - (one.createdAt || 0)));
        },
        () => onChange([])
    );
};

/**
 * Add a target or change an existing one.
 *
 * @return {Promise<object>} { ok: true, id } or { ok: false, message }
 */
export const saveTarget = async (section, target, id = null) => {
    if (database === null) {
        return { ok: false, message: 'Firebase is not set up yet.' };
    }

    const record = {
        ...target,
        section,
        targetBeneficiary: Number(target.targetBeneficiary) || 0,
        targetDisbursement: Number(target.targetDisbursement) || 0,
        updatedAt: Date.now(),
    };

    try {
        if (id === null) {
            const created = push(databaseRef(database, 'targets'));

            await set(created, { ...record, createdAt: Date.now(), createdBy: auth?.currentUser?.uid || null });

            return { ok: true, id: created.key };
        }

        await update(databaseRef(database, `targets/${id}`), record);

        return { ok: true, id };
    } catch (error) {
        return {
            ok: false,
            message: error?.code === 'PERMISSION_DENIED'
                ? 'Your role cannot change targets for this section.'
                : 'Could not save the target. Try again.',
        };
    }
};
