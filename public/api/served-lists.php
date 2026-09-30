<?php

/**
 * Imports a served-list CSV into payout records.
 *
 * The browser may not write payout data, so the file is parsed and stored here.
 * Every import is recorded in the audit log, including the ones that fail.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../src/ServedListReader.php';

const BATCH_SIZE = 500;

requireMethod('POST');

try {
    $admin = new FirebaseAdmin(config()['firebase']);
} catch (RuntimeException $exception) {
    respond(['message' => $exception->getMessage()], 500);
}

// The file arrives as a form upload, so the fields come from $_POST.
$accountId = $admin->accountIdForToken((string) ($_POST['idToken'] ?? ''));

if ($accountId === null) {
    respond(['message' => 'Sign in first.'], 401);
}

$profile = $admin->read('users/' . $accountId);
$role = is_array($profile) ? ($profile['role'] ?? '') : '';

if (! in_array($role, ['ADMIN', 'RDV Focal'], true) || ($profile['status'] ?? '') !== 'Active') {
    respond(['message' => 'Only an active administrator or RDV Focal can import a served list.'], 403);
}

/**
 * Record the import attempt, whether it worked or not.
 *
 * @param array<string, mixed> $profile
 */
function recordImport(FirebaseAdmin $admin, string $accountId, array $profile, string $action, string $activity): void
{
    recordAudit($admin, $accountId, $profile, [
        'module' => 'Import Served List',
        'action' => $action,
        'activity' => $activity,
    ]);
}

/**
 * Stop, explaining why the import did not happen, and note it in the log.
 *
 * @param array<string, mixed> $profile
 */
function refuse(
    FirebaseAdmin $admin,
    string $accountId,
    array $profile,
    string $fileName,
    string $reason,
    int $status = 422
): never {
    recordImport($admin, $accountId, $profile, 'Import failed', trim($fileName . ' — ' . $reason));

    respond(['message' => $reason], $status);
}

$fileName = (string) ($_FILES['file']['name'] ?? '');

if (($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    refuse($admin, $accountId, $profile, $fileName, 'Choose a CSV file to import.');
}

// AICS belongs to CIS and ECT to DRMD, so the programme follows the person's
// section unless an administrator says otherwise.
$program = strtoupper(trim((string) ($_POST['program'] ?? '')));

if ($program === '') {
    $program = ($profile['section'] ?? '') === 'DRMD' ? 'ECT' : 'AICS';
}

if (! in_array($program, ['AICS', 'ECT'], true)) {
    refuse($admin, $accountId, $profile, $fileName, 'The programme must be AICS or ECT.');
}

$disasterType = trim((string) ($_POST['disasterType'] ?? ''));

if ($program === 'ECT' && $disasterType === '') {
    refuse($admin, $accountId, $profile, $fileName, 'Choose the disaster name for an ECT import.');
}

/**
 * Find a place by id or by name, within the level it belongs to.
 *
 * @param array<string, mixed> $geographies
 *
 * @return array{0: string, 1: string}|null the id and name
 */
function findPlace(array $geographies, string $level, string $wanted, ?string $parentId): ?array
{
    if ($wanted === '') {
        return null;
    }

    foreach ($geographies as $id => $place) {
        if (($place['level'] ?? '') !== $level) {
            continue;
        }

        if ($parentId !== null && ($place['parentId'] ?? '') !== $parentId) {
            continue;
        }

        $matchesId = $id === $wanted;
        $matchesName = strcasecmp((string) ($place['name'] ?? ''), $wanted) === 0;

        if ($matchesId || $matchesName) {
            return [$id, (string) $place['name']];
        }
    }

    return null;
}

$geographies = $admin->read('geographies') ?: [];
$province = findPlace($geographies, 'province', trim((string) ($_POST['province'] ?? '')), null);

if ($province === null) {
    refuse($admin, $accountId, $profile, $fileName, 'That province is not in the database.');
}

$municipality = findPlace($geographies, 'municipality', trim((string) ($_POST['municipality'] ?? '')), $province[0]);

if ($municipality === null) {
    refuse(
        $admin,
        $accountId,
        $profile,
        $fileName,
        'That city or municipality does not belong to ' . $province[1] . '.'
    );
}

$barangay = findPlace($geographies, 'barangay', trim((string) ($_POST['barangay'] ?? '')), $municipality[0]);

if ($barangay === null) {
    refuse($admin, $accountId, $profile, $fileName, 'That barangay does not belong to ' . $municipality[1] . '.');
}

$place = [
    'provinceId' => $province[0], 'provinceName' => $province[1],
    'municipalityId' => $municipality[0], 'municipalityName' => $municipality[1],
    'barangayId' => $barangay[0], 'barangayName' => $barangay[1],
];
$where = sprintf('%s, %s, %s', $barangay[1], $municipality[1], $province[1]);

// The same file imported twice would double every figure on the dashboard.
$replacing = ($_POST['replace'] ?? '') === '1';
$earlier = null;

foreach ($admin->findBy('servedLists', 'barangayId', $barangay[0]) as $listId => $list) {
    if (($list['fileName'] ?? '') === $fileName && ($list['program'] ?? '') === $program) {
        $earlier = [$listId, $list];
    }
}

if ($earlier !== null && ! $replacing) {
    $importedOn = date('j M Y', (int) (($earlier[1]['importedAt'] ?? 0) / 1000));

    refuse(
        $admin,
        $accountId,
        $profile,
        $fileName,
        sprintf(
            '"%s" was already imported for %s on %s. Import it again to replace those rows.',
            $fileName,
            $where,
            $importedOn
        ),
        409
    );
}

$reader = new ServedListReader();

try {
    $records = $reader->read((string) $_FILES['file']['tmp_name'], $place);
} catch (RuntimeException $exception) {
    refuse($admin, $accountId, $profile, $fileName, $exception->getMessage());
}

if ($records === []) {
    refuse($admin, $accountId, $profile, $fileName, 'No usable rows were found in the file.');
}

$listId = $admin->push('servedLists', [
    'fileName' => $fileName,
    'program' => $program,
    'disasterType' => $disasterType,
    'provinceId' => $place['provinceId'],
    'municipalityId' => $place['municipalityId'],
    'barangayId' => $place['barangayId'],
    'importedBy' => $accountId,
    'importedByName' => (string) ($profile['name'] ?? 'Unknown'),
    'importedAt' => (int) (microtime(true) * 1000),
    'rowsRead' => $reader->rowsRead(),
    'rowsImported' => count($records),
    'rowsSkipped' => count($reader->skipped()),
]);

// Out with the rows the earlier import created, before the new ones go in.
$replaced = 0;

if ($earlier !== null) {
    foreach ($admin->findBy('payoutRecords', 'servedListId', $earlier[0]) as $recordId => $ignored) {
        $admin->remove('payoutRecords/' . $recordId);
        $replaced++;
    }

    $admin->remove('servedLists/' . $earlier[0]);
}

// Written in batches so one huge request never has to carry the whole list.
$batch = [];

foreach ($records as $index => $record) {
    $batch[$listId . '-' . ($index + 1)] = $record + [
        'program' => $program,
        'disasterType' => $disasterType,
        'servedListId' => $listId,
    ];

    if (count($batch) === BATCH_SIZE) {
        $admin->patch('payoutRecords', $batch);
        $batch = [];
    }
}

if ($batch !== []) {
    $admin->patch('payoutRecords', $batch);
}

$summary = sprintf(
    'Imported %d of %d rows from "%s" for %s · %s — %s',
    count($records),
    $reader->rowsRead(),
    $fileName,
    $program,
    $disasterType !== '' ? $disasterType : 'no disaster name',
    $where
);

if ($reader->skipped() !== []) {
    $summary .= sprintf(' (%d skipped)', count($reader->skipped()));
}

if ($replaced > 0) {
    $summary .= sprintf(', replacing %d earlier rows', $replaced);
}

recordImport($admin, $accountId, $profile, 'Import', $summary);

respond([
    'servedListId' => $listId,
    'rowsRead' => $reader->rowsRead(),
    'rowsImported' => count($records),
    'rowsReplaced' => $replaced,
    'skipped' => array_slice($reader->skipped(), 0, 20),
    'where' => $where,
    'program' => $program,
], 201);
