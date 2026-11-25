import { loadGeographies } from './geographies.js';
import { psgcData } from '../dashboard/psgcData.js';

// Public PSGC reference API. Financial data continues to come from our server.
const endpoint = 'https://psgc.gitlab.io/api';
const cache = new Map();
const normalize = (name) => String(name).toLowerCase().replace(/^city of /, '').replace(/ city$/, '').trim();

const readLocations = async (path) => {
    if (cache.has(path)) return cache.get(path);
    const response = await fetch(`${endpoint}/${path}.json`);
    if (!response.ok) throw new Error('Could not load PSGC locations. Please try again.');
    const places = await response.json();
    if (!Array.isArray(places)) throw new Error('Invalid PSGC location response.');
    cache.set(path, places);
    return places;
};

export const loadDashboardLocations = async (level, parent = null) => {
    let places;
    if (level === 'province') {
        places = psgcData[0].provinces.map((place) => ({
            name: place.name,
            code: place.correspondenceCode,
            psgcCode: place.psgcCode,
        }));
    } else if (parent?.isCity) {
        places = await readLocations(`cities-municipalities/${parent.code}/barangays`);
    } else if (level === 'municipality') {
        places = await readLocations(`provinces/${parent.code}/cities-municipalities`);
        // Group Davao City under Davao del Sur in the dashboard.
        if (parent.code === '112400000' && !places.some((place) => place.code === '112402000')) {
            places = [...places, { name: 'City of Davao', code: '112402000', psgcCode: '1130700000', isCity: true }];
        }
    } else {
        places = await readLocations(`cities-municipalities/${parent.code}/barangays`);
    }

    const stored = await loadGeographies();
    return places.map((place) => {
        const storedLevel = place.isCity ? 'municipality' : level;
        const match = stored.find((entry) => entry.level === storedLevel && (
            [place.code, place.psgcCode, place.psgc10DigitCode].filter(Boolean).includes(String(entry.psgcCode))
            || (normalize(entry.name) === normalize(place.name)
                && (level === 'province' || entry.parentId === parent?.id))
        ));
        return {
            ...place,
            psgcCode: place.psgcCode || place.psgc10DigitCode || place.code,
            id: match?.id || `psgc-${place.code}`,
            storedId: match?.id || null,
            target: 0,
            paid: 0,
            progress: 0,
            amountDisbursed: 0,
        };
    }).sort((one, two) => one.name.localeCompare(two.name));
};
