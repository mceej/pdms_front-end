<?php

/**
 * Entry page for the DSWD Payout Dashboard.
 *
 * In development it loads the Vue app from the Vite dev server (live reload).
 * Otherwise it loads the built files listed in public/build/.vite/manifest.json.
 */

const ENTRY = 'resources/FrontEnd/app.js';

/**
 * Build the <script>/<link> tags for the Vue app.
 *
 * @return string
 */
function assetTags(): string
{
    $devServer = rtrim((string) getenv('VITE_DEV_SERVER'), '/');

    if ($devServer !== '') {
        return sprintf('<script type="module" src="%s/@vite/client"></script>', $devServer)
            . sprintf('<script type="module" src="%s/%s"></script>', $devServer, ENTRY);
    }

    $manifestPath = __DIR__ . '/build/.vite/manifest.json';

    if (! is_file($manifestPath)) {
        return '<p>Run "npm run build" (or start the Vite dev server) to build the front end.</p>';
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    $entry = $manifest[ENTRY] ?? null;

    if ($entry === null) {
        return '<p>The build manifest does not contain ' . ENTRY . '.</p>';
    }

    $tags = '';

    foreach ($entry['css'] ?? [] as $file) {
        $tags .= sprintf('<link rel="stylesheet" href="/build/%s">', $file);
    }

    return $tags . sprintf('<script type="module" src="/build/%s"></script>', $entry['file']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payout Dashboard</title>

    <?= assetTags() ?>
</head>

<body>
    <div id="app"></div>
</body>
</html>
