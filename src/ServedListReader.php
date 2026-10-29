<?php

require_once __DIR__ . '/DashboardMetrics.php';
require_once __DIR__ . '/GeographyIndex.php';

/**
 * Why one row could not become a payout record.
 *
 * Kept here because it is the reader's own business: it travels from whatever
 * noticed the problem up to the loop, which writes it down and carries on. A
 * bad row never stops an import.
 */
class ServedListRowProblem extends RuntimeException
{
}

/**
 * Reads a served-list CSV into payout records.
 *
 * The shape is the one in "Serve List - Template.csv": one beneficiary per
 * row, with the place, the amount, the date, the type of assistance and what
 * it was charged to all named on the row itself.
 *
 * That makes the file, not the form, the record of what happened. The form
 * still says which barangay the import is filed under, and a row that names
 * no place of its own falls back to it, but a row that names one is believed.
 */
class ServedListReader
{
    /**
     * The template's columns. All of them must be present, whatever their
     * order or case, or the file is not a served list and is refused whole.
     */
    public const REQUIRED_COLUMNS = [
        'CONTROL NUMBER',
        'BIRTH DAY',
        'BIRTH MONTH',
        'BIRTH YEAR',
        'SEX',
        'BARANGAY',
        'PROVINCE',
        'CITY/MUNICIPALITY',
        'AMOUNT',
        'DATE LAST SERVED',
        'PAYOUT SITE',
        'TYPE OF ASSISTANCE',
        'CHARGING',
    ];

    /** How many skipped rows are described before the rest are only counted. */
    private const MAX_REASONS = 50;

    /** @var array<int, string> */
    private array $skipped = [];

    private int $skippedCount = 0;

    private int $rowsRead = 0;

    /**
     * Turn the file into records ready for the database.
     *
     * @param array{geographies: GeographyIndex, place: array<string, string>,
     *              program: string, programType: string} $context
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException when the file cannot be read or columns are missing
     */
    public function read(string $path, array $context): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The uploaded file could not be opened.');
        }

        $header = fgetcsv($handle, null, ',', '"', '');

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('The file is empty.');
        }

        $columns = $this->readHeader($header);
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $columns));

        if ($missing !== []) {
            fclose($handle);

            throw new RuntimeException(
                'This is not the served-list template. These columns are missing: '
                . implode(', ', $missing) . '.'
            );
        }

        $records = [];

        // One row at a time, so a long list never has to fit in memory at once.
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($this->isBlank($row)) {
                continue;
            }

            $this->rowsRead++;

            try {
                $records[] = $this->toRecord($this->combine($columns, $row), $context);
            } catch (ServedListRowProblem $problem) {
                $this->skip($problem->getMessage());
            }
        }

        fclose($handle);

        return $records;
    }

    public function rowsRead(): int
    {
        return $this->rowsRead;
    }

    /**
     * @return array<int, string>
     */
    public function skipped(): array
    {
        return $this->skipped;
    }

    public function skippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * The header, trimmed and upper-cased so order and case do not matter.
     *
     * @param array<int, string|null> $header
     *
     * @return array<int, string>
     */
    private function readHeader(array $header): array
    {
        return array_map(
            // A file saved from Excel often carries a byte-order mark on the
            // first heading, which would otherwise hide that column.
            static fn ($name): string => strtoupper(trim(str_replace("\xEF\xBB\xBF", '', (string) $name))),
            $header
        );
    }

    /**
     * Note a row that was left out, describing the first several.
     */
    private function skip(string $reason): void
    {
        $this->skippedCount++;

        if (count($this->skipped) < self::MAX_REASONS) {
            // Plus one for the header, so the number matches the spreadsheet.
            $this->skipped[] = 'row ' . ($this->rowsRead + 1) . ': ' . $reason;
        }
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isBlank(array $row): bool
    {
        return $row === [null] || implode('', array_map(fn ($cell) => trim((string) $cell), $row)) === '';
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string|null> $row
     *
     * @return array<string, string>
     */
    private function combine(array $columns, array $row): array
    {
        $values = [];

        foreach ($columns as $index => $column) {
            $values[$column] = trim((string) ($row[$index] ?? ''));
        }

        return $values;
    }

    /**
     * One row as a payout record.
     *
     * @param array<string, string> $values
     * @param array{geographies: GeographyIndex, place: array<string, string>,
     *              program: string, programType: string} $context
     *
     * @return array<string, mixed>
     *
     * @throws ServedListRowProblem when the row cannot be one
     */
    private function toRecord(array $values, array $context): array
    {
        $servedDate = $this->readDate($values['DATE LAST SERVED']);

        if ($servedDate === null) {
            throw new ServedListRowProblem('DATE LAST SERVED must be a date like 13/03/2026');
        }

        $amountText = str_replace([',', ' '], '', $values['AMOUNT']);

        if (! is_numeric($amountText)) {
            throw new ServedListRowProblem('AMOUNT must be a number');
        }

        $place = $this->placeFor($values, $context);
        $programType = $this->programTypeFor($values['CHARGING'], $context);
        $amount = (float) $amountText;

        return $place + [
            // The template has no paid column: a served list is the record of
            // money handed over, so a row with an amount on it was paid.
            'isPaid' => $amount > 0,
            'disbursedAmount' => $amount,
            'servedDate' => $servedDate,
            'beneficiaryReference' => $values['CONTROL NUMBER'],
            'birthDate' => $this->readBirthDate($values),
            'sex' => $this->readSex($values['SEX']),
            'assistanceType' => $values['TYPE OF ASSISTANCE'],
            'programType' => $programType,
            'program' => $context['program'],
            'payoutSite' => $values['PAYOUT SITE'] !== ''
                ? $values['PAYOUT SITE']
                : $place['barangayName'],
        ];
    }

    /**
     * Where the row happened.
     *
     * @param array<string, string> $values
     * @param array{geographies: GeographyIndex, place: array<string, string>} $context
     *
     * @return array<string, string>
     *
     * @throws ServedListRowProblem when the row names a place nobody knows
     */
    private function placeFor(array $values, array $context): array
    {
        $province = $values['PROVINCE'];
        $municipality = $values['CITY/MUNICIPALITY'];
        $barangay = $values['BARANGAY'];

        // A row that names nowhere belongs where the import was filed.
        if ($province === '' && $municipality === '' && $barangay === '') {
            return $context['place'];
        }

        $place = $context['geographies']->resolve($province, $municipality, $barangay);

        if ($place !== null) {
            return $place;
        }

        // Named but unknown is a mistake worth reporting, not one to paper
        // over by filing the row under whatever the form happened to say.
        throw new ServedListRowProblem(sprintf(
            '%s, %s, %s is not a place in the database',
            $barangay !== '' ? $barangay : '(no barangay)',
            $municipality !== '' ? $municipality : '(no city/municipality)',
            $province !== '' ? $province : '(no province)'
        ));
    }

    /**
     * What the row was charged to.
     *
     * @param array{program: string, programType: string} $context
     *
     * @throws ServedListRowProblem when it is unknown, or belongs elsewhere
     */
    private function programTypeFor(string $charging, array $context): string
    {
        $charging = trim($charging);

        if ($charging === '') {
            return $context['programType'];
        }

        foreach (DashboardMetrics::PROGRAM_TYPES as $known) {
            if (strcasecmp($charging, $known) !== 0) {
                continue;
            }

            // One import is one programme, so a row charged to the other one
            // is a file in the wrong place rather than a row to quietly take.
            if (DashboardMetrics::programOfType($known) !== $context['program']) {
                throw new ServedListRowProblem(
                    sprintf('CHARGING says %s, which is not paid under %s', $known, $context['program'])
                );
            }

            return $known;
        }

        throw new ServedListRowProblem(
            'CHARGING must be one of ' . implode(', ', DashboardMetrics::PROGRAM_TYPES)
        );
    }

    /**
     * A date as the template writes it, as YYYY-MM-DD.
     *
     * The template's own example is 13/03/2026, so the day comes first. A date
     * already written the other way round is taken as it is, because it cannot
     * be meant any other way.
     */
    private function readDate(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $found) === 1) {
            return checkdate((int) $found[2], (int) $found[3], (int) $found[1]) ? $value : null;
        }

        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $value, $found) !== 1) {
            return null;
        }

        [, $day, $month, $year] = $found;

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * The three birth columns as one date, or blank when they do not make one.
     *
     * A birthday is who the person is, not what was paid, so a bad one never
     * costs the row: the payment still counts.
     *
     * @param array<string, string> $values
     */
    private function readBirthDate(array $values): string
    {
        $day = (int) $values['BIRTH DAY'];
        $month = (int) $values['BIRTH MONTH'];
        $year = (int) $values['BIRTH YEAR'];

        if (! checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Stored the one way round, however the file writes it.
     */
    private function readSex(string $value): string
    {
        return match (strtoupper(trim($value))) {
            'M', 'MALE' => 'Male',
            'F', 'FEMALE' => 'Female',
            default => '',
        };
    }
}
