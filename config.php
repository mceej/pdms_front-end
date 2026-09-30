<?php

/**
 * Application configuration.
 *
 * This file sits outside public/ so the web server never serves it.
 * Read environment values here, not in the rest of the code.
 */

return [
    'viteDevServer' => getenv('VITE_DEV_SERVER') ?: '',

    'firebase' => [
        'projectId' => getenv('FIREBASE_PROJECT_ID') ?: 'pdmsfoxi',
        'databaseUrl' => getenv('FIREBASE_DATABASE_URL')
            ?: 'https://pdmsfoxi-default-rtdb.asia-southeast1.firebasedatabase.app',
        // A secret. Kept out of public/ and out of git.
        'serviceAccountPath' => getenv('FIREBASE_SERVICE_ACCOUNT') ?: __DIR__ . '/service-account.json',
    ],
];
