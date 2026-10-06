// Signing in and out, and working out which page the signed-in person gets.
import {
    onAuthStateChanged,
    signInWithEmailAndPassword,
    signOut,
} from 'firebase/auth';
import { get, ref as databaseRef } from 'firebase/database';
import { auth, database, isFirebaseConfigured } from '../firebase/app.js';
import { forgetGeographies } from '../data/geographies.js';

const SIGN_IN_ERRORS = {
    'auth/invalid-email': 'Enter a valid email address.',
    'auth/invalid-credential': 'Wrong email or password.',
    'auth/user-not-found': 'Wrong email or password.',
    'auth/wrong-password': 'Wrong email or password.',
    'auth/user-disabled': 'This account has been disabled. Ask an administrator.',
    'auth/too-many-requests': 'Too many attempts. Wait a moment and try again.',
    'auth/network-request-failed': 'Cannot reach the server. Check your connection.',
};

const NOT_CONFIGURED = 'Firebase is not set up yet. Copy .env.example to .env and fill it in.';

/**
 * Work out which page a profile belongs to.
 *
 * @return {string} one of admin, mancom, rdv-cis, rdv-dbrm
 */
export const pageForProfile = (profile) => {
    if (profile.role === 'ADMIN') {
        return 'admin';
    }

    if (profile.role === 'MANCOM') {
        return 'mancom';
    }

    return profile.section === 'CIS' ? 'rdv-cis' : 'rdv-dbrm';
};

/**
 * Read the profile stored for an account.
 */
const readProfile = async (uid) => {
    const snapshot = await get(databaseRef(database, `users/${uid}`));

    return snapshot.exists() ? snapshot.val() : null;
};

/**
 * Build what the app needs to know about the signed-in person.
 */
const describeUser = async (user) => {
    const profile = await readProfile(user.uid);

    if (profile === null) {
        return { error: 'This account has no profile yet. Ask an administrator.' };
    }

    if (profile.status !== 'Active') {
        return { error: 'This account is inactive. Ask an administrator.' };
    }

    return {
        user: {
            uid: user.uid,
            email: user.email,
            name: profile.name,
            role: profile.role,
            section: profile.section,
            page: pageForProfile(profile),
        },
    };
};

/**
 * Sign in. Returns { user } on success, or { error } with a message to show.
 */
export const signIn = async (email, password) => {
    if (! isFirebaseConfigured) {
        return { error: NOT_CONFIGURED };
    }

    let credential;

    try {
        credential = await signInWithEmailAndPassword(auth, email, password);
    } catch (error) {
        return { error: SIGN_IN_ERRORS[error.code] || 'Unable to sign in. Try again.' };
    }

    const described = await describeUser(credential.user);

    if (described.error) {
        await signOut(auth);
    }

    return described;
};

/**
 * Sign out. The app returns to the login page either way.
 */
export const signOutUser = async () => {
    // Nothing one person loaded should still be in memory for the next.
    forgetGeographies();

    if (! isFirebaseConfigured) {
        return;
    }

    try {
        await signOut(auth);
    } catch {
        // Nothing to do.
    }
};

/**
 * Call back with the signed-in person, or null, whenever that changes.
 * Runs once on start-up, which is what keeps someone signed in across a refresh.
 */
export const watchSession = (onChange) => {
    if (! isFirebaseConfigured) {
        onChange(null);

        return () => {};
    }

    return onAuthStateChanged(auth, async (user) => {
        if (user === null) {
            onChange(null);

            return;
        }

        const described = await describeUser(user);

        if (described.error) {
            await signOutUser();
            onChange(null);

            return;
        }

        onChange(described.user);
    });
};
