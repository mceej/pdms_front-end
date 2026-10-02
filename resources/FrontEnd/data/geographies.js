// Region XI places, used by the location dropdowns.
import { get, ref as databaseRef } from 'firebase/database';
import { database } from '../firebase/app.js';

let cached = null;

/**
 * Read every place once and keep it; the list rarely changes.
 *
 * @return {Promise<Array>} each place with its id, name, level and parentId
 */
export const loadGeographies = async () => {
    if (cached !== null) {
        return cached;
    }

    if (database === null) {
        return [];
    }

    try {
        const snapshot = await get(databaseRef(database, 'geographies'));
        const value = snapshot.val() || {};

        cached = Object.entries(value).map(([id, place]) => ({ id, ...place }));
    } catch {
        return [];
    }

    return cached;
};

/**
 * Drop what is held in memory, so the next read sees fresh places.
 */
export const forgetGeographies = () => {
    cached = null;
};

/**
 * The places at one level, optionally only those under a given parent.
 *
 * @return {Array<string>} names, sorted, ready for a dropdown
 */
export const placeNames = (places, level, parentName = null) => {
    const parent = parentName === null
        ? null
        : places.find((place) => place.name === parentName) || null;

    return places
        .filter((place) => place.level === level)
        .filter((place) => parent === null || place.parentId === parent.id)
        .map((place) => place.name)
        .sort((one, two) => one.localeCompare(two));
};
