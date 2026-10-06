<?php

/**
 * Turns targets and payout records into the figures the dashboard draws.
 *
 * Targets are the denominators: how many people a payout was meant to reach,
 * and how much money it was meant to move. Payout records are what actually
 * happened. Every figure below is one measured against the other.
 *
 * The two sides are stored differently. A payout record carries the ids of its
 * province, municipality and barangay, resolved at import time. A target
 * carries only their names, because the target form never stored ids. So the
 * names are looked up in geographies/ first, and the join happens on ids.
 */
class DashboardMetrics
{
    public const LEVELS = ['province', 'municipality', 'barangay'];

    private const KEY_FIELD = [
        'province' => 'provinceId',
        'municipality' => 'municipalityId',
        'barangay' => 'barangayId',
    ];

    /** @var array<string, array<string, mixed>> keyed by geography id */
    private array $geographies;

    /** @var array<int, array<string, mixed>> */
    private array $payouts = [];

    /** @var array<int, array<string, mixed>> */
    private array $targets = [];

    /** @var array<string, array<int, array{id: string, parentId: ?string}>> */
    private array $placeIndex = [];

    private int $targetsWithoutPlace = 0;

    private int $targetsWithoutProgram = 0;

    private int $targetsAboveLevel = 0;

    private bool $payoutsCarryAssistanceType = false;

    /**
     * @param array<string, mixed> $payoutRecords the payoutRecords branch
     * @param array<string, mixed> $targets the targets branch
     * @param array<string, mixed> $geographies the geographies branch
     */
    public function __construct(array $payoutRecords, array $targets, array $geographies)
    {
        $this->geographies = array_filter($geographies, 'is_array');
        $this->buildPlaceIndex();

        foreach ($payoutRecords as $record) {
            if (! is_array($record)) {
                continue;
            }

            $payout = $this->readPayout($record);
            $this->payoutsCarryAssistanceType = $this->payoutsCarryAssistanceType || $payout['assistanceType'] !== '';
            $this->payouts[] = $payout;
        }

        foreach ($targets as $target) {
            if (! is_array($target)) {
                continue;
            }

            $prepared = $this->readTarget($target);

            if ($prepared === null) {
                $this->targetsWithoutPlace++;

                continue;
            }

            $this->targets[] = $prepared;
        }
    }

    /**
     * The whole dashboard payload for one set of query parameters.
     *
     * The cards and the rows answer the same question at different grains: the
     * cards for the place being looked at, the rows for the places inside it.
     * Drilling into a municipality re-reads both, so the cards never disagree
     * with the table underneath them.
     *
     * @param array{group: string, program: ?string, start_date: ?string, end_date: ?string,
     *              province_id: ?string, municipality_id: ?string, barangay_id: ?string} $query
     *
     * @return array<string, mixed>
     */
    public function build(array $query, ?int $lastUpdated = null): array
    {
        $payouts = $this->payoutsMatching($query);
        $targets = $this->targetsMatching($query);

        $scopedPayouts = $this->within($payouts, $query);
        $scopedTargets = $this->within($targets, $query);

        $rows = $this->rowsFor($query['group'], $scopedTargets, $scopedPayouts);

        return [
            'kpi' => $this->headlineFigures($scopedTargets, $scopedPayouts),
            'grouping' => $query['group'],
            'rows' => $rows,
            'total' => $this->totalRow($rows),
            'filters' => [
                'program' => $query['program'],
                'disaster_type' => $query['disaster_type'],
                'assistance_type' => $query['assistance_type'],
                'payout_site' => $query['payout_site'],
                'start_date' => $query['start_date'],
                'end_date' => $query['end_date'],
                'province_id' => $query['province_id'],
                'municipality_id' => $query['municipality_id'],
                'barangay_id' => $query['barangay_id'],
            ],
            // Each menu lists what is still reachable with its own choice set
            // aside, so picking a site never hides the other sites.
            'payout_sites' => $this->distinct(
                $this->within($this->payoutsMatching($query, ['payout_site']), $query),
                'payoutSite'
            ),
            'disaster_types' => $this->distinct(
                $this->payoutsMatching($query, ['disaster_type', 'start_date', 'end_date']),
                'disasterType'
            ),
            'last_updated' => $lastUpdated,
            'last_updated_iso' => $lastUpdated === null
                ? null
                : gmdate('c', intdiv($lastUpdated, 1000)),
            'warnings' => $this->warningsFor($query, $targets, $payouts),
        ];
    }

    /**
     * The six figures on the cards, plus the progress they add up to.
     *
     * Beneficiary counts and peso amounts are kept apart on purpose, and named
     * for what they hold: the balance of people still to pay and the balance of
     * money still to move are different questions, and the cards ask both.
     *
     * @param array<int, array<string, mixed>> $targets
     * @param array<int, array<string, mixed>> $payouts
     *
     * @return array<string, mixed>
     */
    private function headlineFigures(array $targets, array $payouts): array
    {
        $targetBeneficiaries = 0;
        $totalBalance = 0.0;

        foreach ($targets as $target) {
            $targetBeneficiaries += $target['beneficiaries'];
            $totalBalance += $target['disbursement'];
        }

        $paid = 0;
        $disbursed = 0.0;

        foreach ($payouts as $payout) {
            $disbursed += $payout['disbursedAmount'];

            if ($payout['isPaid']) {
                $paid++;
            }
        }

        return [
            // Pesos the targets ask for, and people they ask for.
            'total_balance' => round($totalBalance, 2),
            'target_beneficiaries' => $targetBeneficiaries,
            // People paid, and pesos moved.
            'total_paid' => $paid,
            'total_disbursed' => round($disbursed, 2),
            // What is left of each: pesos still to move, people still to pay.
            'unpaid_balance' => round(max(0.0, $totalBalance - $disbursed), 2),
            'unpaid_beneficiaries' => max(0, $targetBeneficiaries - $paid),
            'progress' => [
                'paid' => $paid,
                'target' => $targetBeneficiaries,
                'fraction' => $targetBeneficiaries > 0 ? round($paid / $targetBeneficiaries, 4) : 0.0,
                'percent' => $this->percent($paid, $targetBeneficiaries),
            ],
        ];
    }

    /**
     * One row per place at the requested level, targets and payouts joined.
     *
     * A place with a target but no payouts yet belongs here just as much as one
     * with payouts, so the rows are the union of both sides rather than only
     * the places somebody has already been paid in.
     *
     * @param array<int, array<string, mixed>> $targets
     * @param array<int, array<string, mixed>> $payouts
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsFor(string $level, array $targets, array $payouts): array
    {
        $field = self::KEY_FIELD[$level];
        $groups = [];
        $this->targetsAboveLevel = 0;

        foreach ($targets as $target) {
            $id = $target['place'][$field];

            // A target set for a whole province cannot be shown per barangay.
            if ($id === null) {
                $this->targetsAboveLevel++;

                continue;
            }

            $groups[$id] ??= $this->emptyGroup();
            $groups[$id]['target'] += $target['beneficiaries'];
            $groups[$id]['target_amount'] += $target['disbursement'];
        }

        foreach ($payouts as $payout) {
            $id = $payout[$field];

            if ($id === '') {
                continue;
            }

            $groups[$id] ??= $this->emptyGroup();
            $groups[$id]['amount_disbursed'] += $payout['disbursedAmount'];

            if ($payout['isPaid']) {
                $groups[$id]['paid']++;
            }
        }

        $rows = [];

        foreach ($groups as $id => $group) {
            $rows[] = $this->finishRow((string) $id, $this->nameFor((string) $id), $group);
        }

        usort($rows, fn (array $one, array $two) => strcasecmp($one['name'], $two['name']));

        return $rows;
    }

    /**
     * The Total row for exactly the rows above it.
     *
     * Its progress is worked out from the summed figures, not averaged from the
     * rows, so a barangay with ten beneficiaries cannot weigh as much as one
     * with ten thousand.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function totalRow(array $rows): array
    {
        $group = $this->emptyGroup();

        foreach ($rows as $row) {
            $group['target'] += $row['target'];
            $group['paid'] += $row['paid'];
            $group['target_amount'] += $row['target_amount'];
            $group['amount_disbursed'] += $row['amount_disbursed'];
        }

        $total = $this->finishRow('total', 'Total', $group);
        $total['rows'] = count($rows);

        return $total;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGroup(): array
    {
        return ['target' => 0, 'paid' => 0, 'target_amount' => 0.0, 'amount_disbursed' => 0.0];
    }

    /**
     * @param array<string, mixed> $group
     *
     * @return array<string, mixed>
     */
    private function finishRow(string $id, string $name, array $group): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'target' => $group['target'],
            'paid' => $group['paid'],
            'remaining' => max(0, $group['target'] - $group['paid']),
            'progress' => $this->percent($group['paid'], $group['target']),
            'target_amount' => round($group['target_amount'], 2),
            'amount_disbursed' => round($group['amount_disbursed'], 2),
            'balance' => round(max(0.0, $group['target_amount'] - $group['amount_disbursed']), 2),
        ];
    }

    /**
     * The filters that were actually asked for, minus any to be left out.
     *
     * Leaving one out is how a menu lists the options it would otherwise hide:
     * the payout sites are counted with the chosen site set aside.
     *
     * @param array<string, mixed> $query
     * @param array<int, string> $ignore
     *
     * @return array<string, string>
     */
    private function activeFilters(array $query, array $ignore): array
    {
        $filters = [];

        foreach (['program', 'disaster_type', 'assistance_type', 'payout_site', 'start_date', 'end_date'] as $name) {
            $value = $query[$name] ?? null;

            if ($value !== null && $value !== '' && ! in_array($name, $ignore, true)) {
                $filters[$name] = (string) $value;
            }
        }

        return $filters;
    }

    /**
     * Payout records left once everything but the place has been applied.
     *
     * @param array<string, mixed> $query
     * @param array<int, string> $ignore filters to leave out
     *
     * @return array<int, array<string, mixed>>
     */
    private function payoutsMatching(array $query, array $ignore = []): array
    {
        $wanted = $this->activeFilters($query, $ignore);

        $fields = [
            'program' => 'program',
            'disaster_type' => 'disasterType',
            'assistance_type' => 'assistanceType',
            'payout_site' => 'payoutSite',
        ];

        return array_values(array_filter($this->payouts, function (array $payout) use ($wanted, $fields) {
            foreach ($fields as $name => $field) {
                if (isset($wanted[$name]) && strcasecmp($payout[$field], $wanted[$name]) !== 0) {
                    return false;
                }
            }

            if (isset($wanted['start_date']) && $payout['servedDate'] < $wanted['start_date']) {
                return false;
            }

            return ! (isset($wanted['end_date']) && $payout['servedDate'] > $wanted['end_date']);
        }));
    }

    /**
     * Targets left once everything but the place has been applied.
     *
     * A target covers a stretch of days, so it counts when its stretch overlaps
     * the dates being asked about at all. One without dates covers everything.
     *
     * The same disaster is a name on a target and a type on a payout record, so
     * the one filter reads a different field on each side.
     *
     * The payout site is deliberately not applied here. A target is set for a
     * barangay, and the site names the two branches hold do not come from the
     * same list, so narrowing the targets by one empties the denominator and
     * every progress figure reads zero. Choosing a site narrows what was paid,
     * against the whole target for the place.
     *
     * @param array<string, mixed> $query
     * @param array<int, string> $ignore filters to leave out
     *
     * @return array<int, array<string, mixed>>
     */
    private function targetsMatching(array $query, array $ignore = []): array
    {
        $wanted = $this->activeFilters($query, $ignore);
        $this->targetsWithoutProgram = 0;

        $fields = [
            'disaster_type' => 'disasterName',
            'assistance_type' => 'assistanceType',
        ];

        return array_values(array_filter($this->targets, function (array $target) use ($wanted, $fields) {
            if (isset($wanted['program'])) {
                if ($target['program'] === null) {
                    $this->targetsWithoutProgram++;

                    return false;
                }

                if ($target['program'] !== $wanted['program']) {
                    return false;
                }
            }

            foreach ($fields as $name => $field) {
                if (isset($wanted[$name]) && strcasecmp($target[$field], $wanted[$name]) !== 0) {
                    return false;
                }
            }

            if (isset($wanted['end_date']) && $target['dateStart'] !== null && $target['dateStart'] > $wanted['end_date']) {
                return false;
            }

            return ! (
                isset($wanted['start_date'])
                && $target['dateEnd'] !== null
                && $target['dateEnd'] < $wanted['start_date']
            );
        }));
    }

    /**
     * Narrow either side to the place being looked at.
     *
     * @param array<int, array<string, mixed>> $records
     * @param array<string, mixed> $query
     *
     * @return array<int, array<string, mixed>>
     */
    private function within(array $records, array $query): array
    {
        $wanted = array_filter([
            'provinceId' => $query['province_id'],
            'municipalityId' => $query['municipality_id'],
            'barangayId' => $query['barangay_id'],
        ], fn ($value) => $value !== null && $value !== '');

        if ($wanted === []) {
            return $records;
        }

        return array_values(array_filter($records, function (array $record) use ($wanted) {
            $place = $record['place'] ?? $record;

            foreach ($wanted as $field => $id) {
                if (($place[$field] ?? null) !== $id) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * The values a field takes across some records, sorted, with blanks dropped.
     *
     * @param array<int, array<string, mixed>> $records
     *
     * @return array<int, string>
     */
    private function distinct(array $records, string $field): array
    {
        $values = [];

        foreach ($records as $record) {
            $value = trim((string) ($record[$field] ?? ''));

            if ($value !== '') {
                $values[$value] = true;
            }
        }

        $values = array_keys($values);
        sort($values);

        return $values;
    }

    /**
     * Anything the figures above cannot say for themselves.
     *
     * @param array<string, mixed> $query
     * @param array<int, array<string, mixed>> $targets
     * @param array<int, array<string, mixed>> $payouts
     *
     * @return array<int, string>
     */
    private function warningsFor(array $query, array $targets, array $payouts): array
    {
        $warnings = [];

        if (($query['assistance_type'] ?? null) !== null && ! $this->payoutsCarryAssistanceType) {
            $warnings[] = 'No payout record stores an assistance type yet, '
                . 'so filtering by one matches nothing until the import starts saving it.';
        }

        if ($this->targetsWithoutPlace > 0) {
            $warnings[] = sprintf(
                '%d target(s) left out: their province, city or barangay is not in the database.',
                $this->targetsWithoutPlace
            );
        }

        if ($this->targetsWithoutProgram > 0) {
            $warnings[] = sprintf(
                '%d target(s) left out: the record does not say which programme they belong to.',
                $this->targetsWithoutProgram
            );
        }

        if ($this->targetsAboveLevel > 0) {
            $warnings[] = sprintf(
                '%d target(s) are set above this level, so they count in the cards but not in the rows.',
                $this->targetsAboveLevel
            );
        }

        if ($targets === [] && $payouts !== []) {
            $warnings[] = 'No target covers these filters, so every progress figure reads zero.';
        }

        return $warnings;
    }

    /**
     * A payout record, with only the fields the figures need.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private function readPayout(array $record): array
    {
        return [
            'program' => strtoupper(trim((string) ($record['program'] ?? ''))),
            'disasterType' => (string) ($record['disasterType'] ?? ''),
            // The import does not store this yet, so it is blank on every row.
            'assistanceType' => (string) ($record['assistanceType'] ?? ''),
            'payoutSite' => (string) ($record['payoutSite'] ?? ''),
            'provinceId' => (string) ($record['provinceId'] ?? ''),
            'municipalityId' => (string) ($record['municipalityId'] ?? ''),
            'barangayId' => (string) ($record['barangayId'] ?? ''),
            'disbursedAmount' => (float) ($record['disbursedAmount'] ?? 0),
            'isPaid' => (bool) ($record['isPaid'] ?? false),
            'servedDate' => (string) ($record['servedDate'] ?? ''),
        ];
    }

    /**
     * A target, with its place names turned into ids.
     *
     * @param array<string, mixed> $target
     *
     * @return array<string, mixed>|null null when the place cannot be found
     */
    private function readTarget(array $target): ?array
    {
        $place = $this->resolvePlace(
            trim((string) ($target['provinceName'] ?? '')),
            trim((string) ($target['municipalityName'] ?? '')),
            trim((string) ($target['barangayName'] ?? ''))
        );

        if ($place === null) {
            return null;
        }

        $dateStart = trim((string) ($target['dateStart'] ?? ''));
        $dateEnd = trim((string) ($target['dateEnd'] ?? ''));

        return [
            'place' => $place,
            'program' => $this->programOf($target),
            'disasterName' => (string) ($target['disasterName'] ?? ''),
            'assistanceType' => (string) ($target['assistanceType'] ?? ''),
            'payoutSite' => (string) ($target['payoutSite'] ?? ''),
            'beneficiaries' => (int) ($target['targetBeneficiary'] ?? 0),
            'disbursement' => (float) ($target['targetDisbursement'] ?? 0),
            'dateStart' => $dateStart === '' ? null : $dateStart,
            'dateEnd' => $dateEnd === '' ? null : $dateEnd,
        ];
    }

    /**
     * Which programme a target belongs to.
     *
     * Older targets only say which section wrote them, and AICS belongs to CIS
     * while ECT belongs to DRMD, so the section answers when the type does not.
     *
     * @param array<string, mixed> $target
     */
    private function programOf(array $target): ?string
    {
        $payoutType = strtoupper(trim((string) ($target['payoutType'] ?? '')));

        if (in_array($payoutType, ['AICS', 'ECT'], true)) {
            return $payoutType;
        }

        return match (strtoupper(trim((string) ($target['section'] ?? '')))) {
            'CIS' => 'AICS',
            'DRMD' => 'ECT',
            default => null,
        };
    }

    /**
     * Find the three ids behind three place names.
     *
     * Barangay names repeat across the region, so each level is looked up
     * inside the one above it rather than on its own.
     *
     * @return array{provinceId: string, municipalityId: ?string, barangayId: ?string}|null
     */
    private function resolvePlace(string $province, string $municipality, string $barangay): ?array
    {
        if ($province === '') {
            return null;
        }

        $provinceId = $this->idFor('province', $province, null);

        if ($provinceId === null) {
            return null;
        }

        $municipalityId = $municipality === ''
            ? null
            : $this->idFor('municipality', $municipality, $provinceId);

        if ($municipality !== '' && $municipalityId === null) {
            return null;
        }

        $barangayId = $barangay === '' || $municipalityId === null
            ? null
            : $this->idFor('barangay', $barangay, $municipalityId);

        if ($barangay !== '' && $barangayId === null) {
            return null;
        }

        return [
            'provinceId' => $provinceId,
            'municipalityId' => $municipalityId,
            'barangayId' => $barangayId,
        ];
    }

    private function idFor(string $level, string $name, ?string $parentId): ?string
    {
        foreach ($this->placeIndex[$level][strtolower($name)] ?? [] as $place) {
            if ($parentId === null || $place['parentId'] === $parentId) {
                return $place['id'];
            }
        }

        return null;
    }

    /**
     * Group the places by level and lowercased name once, so that resolving a
     * target is a lookup rather than a walk through every place in the region.
     */
    private function buildPlaceIndex(): void
    {
        foreach ($this->geographies as $id => $place) {
            $level = (string) ($place['level'] ?? '');
            $name = strtolower(trim((string) ($place['name'] ?? '')));

            if ($level === '' || $name === '') {
                continue;
            }

            $this->placeIndex[$level][$name][] = [
                'id' => (string) $id,
                'parentId' => isset($place['parentId']) ? (string) $place['parentId'] : null,
            ];
        }
    }

    private function nameFor(string $id): string
    {
        return (string) ($this->geographies[$id]['name'] ?? 'Unknown place');
    }

    private function percent(int $paid, int $target): float
    {
        return $target > 0 ? round(($paid / $target) * 100, 2) : 0.0;
    }
}
