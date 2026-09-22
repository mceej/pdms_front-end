<?php

/**
 * Application configuration.
 *
 * This file sits outside public/ so the web server never serves it.
 * Read environment values here, not in the rest of the code.
 */

return [
    'viteDevServer' => getenv('VITE_DEV_SERVER') ?: '',

    // Hash of the admin password, created with password_hash().
    // Change it with: php -r 'echo password_hash("new password", PASSWORD_DEFAULT);'
    'admin' => [
        'username' => getenv('ADMIN_USERNAME') ?: 'admin',
        'passwordHash' => getenv('ADMIN_PASSWORD_HASH')
            ?: '$2y$12$z2trcpIb/KKcqRsXfJn0LOkgZMWq.bvu2RrsB6MT4yebkE9niuZdy',
    ],
];
