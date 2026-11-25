const endpoint = 'https://psgc.gitlab.io/api';
const cache = new Map();

const readLocations = async (path) => {
    if (!cache.has(path)) {
        const request = fetch(`${endpoint}/${path}/`)
            .then(async (response) => {
                if (!response.ok) throw new Error('Could not load locations.');
                const places = await response.json();
                if (!Array.isArray(places)) throw new Error('Invalid location response.');
                return places.map(({ code, name }) => ({ code, name }))
                    .sort((one, two) => one.name.localeCompare(two.name));
            })
            .catch((error) => {
                cache.delete(path);
                throw error;
            });
        cache.set(path, request);
    }
    return cache.get(path);
};

export const loadPsgcProvinces = async () => {
    return psgcData[0].provinces
        .map(({ name, correspondenceCode }) => ({ code: correspondenceCode, name }))
        .sort((one, two) => one.name.localeCompare(two.name));
};

export const loadPsgcMunicipalities = (provinceCode) => readLocations(
    `provinces/${provinceCode}/cities-municipalities`,
);

export const loadPsgcBarangays = (municipalityCode) => readLocations(
    `cities-municipalities/${municipalityCode}/barangays`,
);
import { psgcData } from '../dashboard/psgcData.js';

