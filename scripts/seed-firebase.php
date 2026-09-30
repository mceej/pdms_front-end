<?php

/**
 * Fills the Realtime Database with sample data.
 *
 * Run it from the project root:  php scripts/seed-firebase.php
 * Add --force to overwrite branches that already hold records.
 *
 * It never touches users/, so logins and profiles are safe.
 */

require_once __DIR__ . '/../src/FirebaseAdmin.php';

$config = require __DIR__ . '/../config.php';
$admin = new FirebaseAdmin($config['firebase']);
$force = in_array('--force', $argv, true);

/**
 * Stop a branch from being overwritten by accident.
 */
function guard(FirebaseAdmin $admin, string $branch, bool $force): bool
{
    $existing = $admin->read($branch);

    if (is_array($existing) && $existing !== [] && ! $force) {
        printf("%-14s skipped, it already holds %d records (use --force)\n", $branch, count($existing));

        return false;
    }

    return true;
}

$sample = json_decode((string) file_get_contents(__DIR__ . '/../resources/FrontEnd/mock/payoutData.json'), true);

// Locations, keyed the way the front end expects.
if (guard($admin, 'geographies', $force)) {
    $geographies = [];

    foreach ($sample['geographies'] as $place) {
        $geographies['g' . $place['id']] = [
            'psgcCode' => (string) $place['psgc_code'],
            'name' => $place['name'],
            'level' => $place['level'],
            'parentId' => $place['parent_id'] === null ? null : 'g' . $place['parent_id'],
        ];
    }

    $admin->write('geographies', $geographies);
    printf("%-14s %d locations\n", 'geographies', count($geographies));
}

// Payout records, with the location names copied in so the dashboard needs no lookups.
if (guard($admin, 'payoutRecords', $force)) {
    $names = [];

    foreach ($sample['geographies'] as $place) {
        $names[$place['id']] = $place['name'];
    }

    $records = [];

    foreach ($sample['records'] as $record) {
        $records['r' . $record['id']] = [
            'program' => $record['program'],
            'disasterType' => $record['disaster_type'],
            'provinceId' => 'g' . $record['province_id'],
            'provinceName' => $names[$record['province_id']] ?? 'Unknown',
            'municipalityId' => 'g' . $record['municipality_id'],
            'municipalityName' => $names[$record['municipality_id']] ?? 'Unknown',
            'barangayId' => 'g' . $record['barangay_id'],
            'barangayName' => $names[$record['barangay_id']] ?? 'Unknown',
            'payoutSite' => $record['payout_site'],
            'targetAmount' => (float) $record['target_amount'],
            'disbursedAmount' => (float) $record['disbursed_amount'],
            'isPaid' => (bool) $record['is_paid'],
            'servedDate' => $record['served_date'],
        ];
    }

    $admin->write('payoutRecords', $records);
    printf("%-14s %d records\n", 'payoutRecords', count($records));
}

// Targets for both sections, matching what the pages used to show.
if (guard($admin, 'targets', $force)) {
    $programTypes = ['AICS', 'CRA', 'Uplift', 'Akap'];
    $disasterNames = [
        'Earthquake', 'Flood', 'Typhoon', 'Landslide', 'Storm Surge',
        'Volcanic Eruption', 'Drought', 'Fire', 'Heavy Rain', 'Tropical Storm',
    ];
    $payoutSites = ['Barangay A', 'Barangay B', 'Barangay C', 'Barangay D', 'Barangay E'];
    $targets = [];

    foreach (['CIS', 'DRMD'] as $section) {
        $isDrmd = $section === 'DRMD';

        for ($index = 0; $index < 10; $index++) {
            $target = [
                'section' => $section,
                'payoutType' => $isDrmd ? 'ECT' : 'AICS',
                'targetBeneficiary' => 1000000 + $index * 250000,
                'targetDisbursement' => 1000000 + $index * 250000,
                'payoutSite' => $payoutSites[$index % 5],
                'dateStart' => '2026-09-30',
                'dateEnd' => '2026-10-05',
                'createdAt' => (int) (microtime(true) * 1000) - $index * 1000,
            ];

            if ($isDrmd) {
                $target['disasterName'] = $disasterNames[$index % count($disasterNames)];
            } else {
                $target['programType'] = $programTypes[$index % count($programTypes)];
                $target['assistanceType'] = 'Cash Assistance';
            }

            $targets[strtolower($section) . '-' . ($index + 1)] = $target;
        }
    }

    $admin->write('targets', $targets);
    printf("%-14s %d targets (%d per section)\n", 'targets', count($targets), count($targets) / 2);
}

echo "\ndone. users/ was not touched.\n";
