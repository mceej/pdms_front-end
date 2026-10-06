<?php

/**
 * Everything the dashboard draws, in one request.
 *
 * The browser used to pull the whole payoutRecords branch down and add it up
 * itself. This endpoint does the adding up here instead, so a page load is one
 * call rather than a download of every record ever imported.
 *
 *   GET /api/metrics.php?group=municipality&province_id=g1&program=aics
 *   Authorization: Bearer <Firebase ID token>
 *
 * Parameters, all optional:
 *   group                     province | municipality | barangay
 *   province_id, municipality_id, barangay_id     ids, from geographies/
 *   province, municipality, barangay              or their names instead
 *   program                   aics | ect
 *   disaster_type             named on a target, typed on a payout record
 *   assistance_type           e.g. Cash Assistance
 *   payout_site
 *   start_date, end_date      YYYY-MM-DD, against the date a payout was served
 *
 * Every figure answers for the place being looked at: the cards for that place,
 * the rows for the places inside it. Ask for nothing and the place is the whole
 * of Region XI.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/DashboardMetrics.php';

requireMethod('GET');

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 500);
}

$idToken = bearerToken();

if ($idToken === '') {
    respond(['message' => 'Sign in first.'], 401);
}

// Turned away before anything is read. Google still has the final say below.
if (! looksLikeIdToken($idToken, (string) config()['firebase']['projectId'])) {
    respond(['message' => 'Your session has expired. Sign in again.'], 401);
}

/* Everything this request needs, asked for in one go: who is calling, and the
   four branches the figures come from. None of them waits on another, and over
   a link to the database's region a round trip costs more than all the work
   done with the answer. Only the newest import is wanted from servedLists. */
$signedIn = $admin->readManyForToken($idToken, [
    'users' => 'users',
    'geographies' => 'geographies',
    'targets' => 'targets',
    'payoutRecords' => 'payoutRecords',
    'newestImport' => 'servedLists?orderBy=' . urlencode('"importedAt"') . '&limitToLast=1',
]);

if ($signedIn['accountId'] === null) {
    respond(['message' => 'Your session has expired. Sign in again.'], 401);
}

$data = $signedIn['values'];

requireActiveProfile($data['users'][$signedIn['accountId']] ?? null);

$geographies = $data['geographies'] ?? [];

/**
 * Read one date parameter, or stop if it is not a date.
 */
function dateParam(string $name): ?string
{
    $value = trim((string) ($_GET[$name] ?? ''));

    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
        respond(['message' => $name . ' must look like 2026-09-15.'], 422);
    }

    return $value;
}

/**
 * Read one place parameter, given either as an id or as a name.
 *
 * @param array<string, mixed> $geographies
 */
function placeParam(array $geographies, string $level, ?string $parentId): ?string
{
    $id = trim((string) ($_GET[$level . '_id'] ?? ''));

    if ($id !== '') {
        if (! isset($geographies[$id]) || ($geographies[$id]['level'] ?? '') !== $level) {
            respond(['message' => 'That ' . $level . ' is not in the database.'], 422);
        }

        return $id;
    }

    $name = trim((string) ($_GET[$level] ?? ''));

    if ($name === '') {
        return null;
    }

    foreach ($geographies as $candidate => $place) {
        if (($place['level'] ?? '') !== $level) {
            continue;
        }

        if ($parentId !== null && ($place['parentId'] ?? '') !== $parentId) {
            continue;
        }

        if (strcasecmp((string) ($place['name'] ?? ''), $name) === 0) {
            return (string) $candidate;
        }
    }

    respond(['message' => 'That ' . $level . ' is not in the database.'], 422);
}

$provinceId = placeParam($geographies, 'province', null);
$municipalityId = placeParam($geographies, 'municipality', $provinceId);
$barangayId = placeParam($geographies, 'barangay', $municipalityId);

// Without a grouping, show one level under whatever was picked, which is what
// drilling into a place is for.
$group = strtolower(trim((string) ($_GET['group'] ?? '')));

if ($group === '') {
    $group = match (true) {
        $municipalityId !== null, $barangayId !== null => 'barangay',
        $provinceId !== null => 'municipality',
        default => 'province',
    };
}

if (! in_array($group, DashboardMetrics::LEVELS, true)) {
    respond(['message' => 'group must be province, municipality or barangay.'], 422);
}

$program = strtoupper(trim((string) ($_GET['program'] ?? '')));

if ($program !== '' && ! in_array($program, ['AICS', 'ECT'], true)) {
    respond(['message' => 'program must be aics or ect.'], 422);
}

/**
 * Read one free-text filter, or null when it was not asked for.
 */
function textParam(string $name): ?string
{
    $value = trim((string) ($_GET[$name] ?? ''));

    return $value === '' ? null : $value;
}

$startDate = dateParam('start_date');
$endDate = dateParam('end_date');

if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
    respond(['message' => 'start_date comes after end_date.'], 422);
}

/**
 * When the figures last changed: the newest import, or the newest target.
 *
 * @param array<string, mixed> $newestImport
 * @param array<string, mixed> $targets
 */
function lastUpdated(array $newestImport, array $targets): ?int
{
    $newest = 0;

    foreach ($newestImport as $list) {
        if (is_array($list)) {
            $newest = max($newest, (int) ($list['importedAt'] ?? 0));
        }
    }

    foreach ($targets as $target) {
        if (is_array($target)) {
            $newest = max($newest, (int) ($target['updatedAt'] ?? $target['createdAt'] ?? 0));
        }
    }

    return $newest > 0 ? $newest : null;
}

$targets = $data['targets'] ?? [];
$metrics = new DashboardMetrics($data['payoutRecords'] ?? [], $targets, $geographies);

respond($metrics->build([
    'group' => $group,
    'program' => $program === '' ? null : $program,
    'disaster_type' => textParam('disaster_type'),
    'assistance_type' => textParam('assistance_type'),
    'payout_site' => textParam('payout_site'),
    'start_date' => $startDate,
    'end_date' => $endDate,
    'province_id' => $provinceId,
    'municipality_id' => $municipalityId,
    'barangay_id' => $barangayId,
], lastUpdated($data['newestImport'] ?? [], $targets)));
