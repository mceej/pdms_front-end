<?php

/**
 * Reads a served-list CSV into payout records.
 *
 * The file carries one beneficiary per row. Where the payout happened comes
 * from the import form, not from the file, so every row lands in the same
 * barangay.
 */
class ServedListReader
{
    public const REQUIRED_COLUMNS = ['served_date', 'is_paid', 'disbursed_amount'];

    public const OPTIONAL_COLUMNS = ['beneficiary_reference', 'target_amount', 'payout_site'];

    /** @var array<int, string> */
    private array $skipped = [];

    private int $rowsRead = 0;

    /**
     * Turn the file into records ready for the database.
     *
     * @param array<string, mixed> $place the barangay and its parents, from the form
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException when the file cannot be read or columns are missing
     */
    public function read(string $path, array $place): array
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

        $columns = array_map(fn ($name) => strtolower(trim((string) $name)), $header);
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $columns));

        if ($missing !== []) {
            fclose($handle);

            throw new RuntimeException('The file is missing these columns: ' . implode(', ', $missing) . '.');
        }

        $records = [];

        // One row at a time, so a long list never has to fit in memory at once.
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($this->isBlank($row)) {
                continue;
            }

            $this->rowsRead++;
            $values = $this->combine($columns, $row);
            $problem = $this->problemWith($values);

            if ($problem !== null) {
                $this->skipped[] = 'row ' . ($this->rowsRead + 1) . ': ' . $problem;

                continue;
            }

            $records[] = $this->toRecord($values, $place);
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
     * Describe what is wrong with a row, or null when it is fine.
     *
     * @param array<string, string> $values
     */
    private function problemWith(array $values): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['served_date'])) {
            return 'served_date must look like 2026-09-15';
        }

        if ($this->readBoolean($values['is_paid']) === null) {
            return 'is_paid must be true or false';
        }

        if (! is_numeric($values['disbursed_amount'])) {
            return 'disbursed_amount must be a number';
        }

        if (($values['target_amount'] ?? '') !== '' && ! is_numeric($values['target_amount'])) {
            return 'target_amount must be a number';
        }

        return null;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, mixed> $place
     *
     * @return array<string, mixed>
     */
    private function toRecord(array $values, array $place): array
    {
        $disbursed = (float) $values['disbursed_amount'];
        $target = ($values['target_amount'] ?? '') === '' ? $disbursed : (float) $values['target_amount'];

        return [
            'provinceId' => $place['provinceId'],
            'provinceName' => $place['provinceName'],
            'municipalityId' => $place['municipalityId'],
            'municipalityName' => $place['municipalityName'],
            'barangayId' => $place['barangayId'],
            'barangayName' => $place['barangayName'],
            'payoutSite' => ($values['payout_site'] ?? '') !== ''
                ? $values['payout_site']
                : $place['barangayName'],
            'beneficiaryReference' => $values['beneficiary_reference'] ?? '',
            'targetAmount' => $target,
            'disbursedAmount' => $disbursed,
            'isPaid' => (bool) $this->readBoolean($values['is_paid']),
            'servedDate' => $values['served_date'],
        ];
    }

    private function readBoolean(string $value): ?bool
    {
        $text = strtolower($value);

        if (in_array($text, ['1', 'true', 'yes', 'y', 'paid'], true)) {
            return true;
        }

        if (in_array($text, ['0', 'false', 'no', 'n', 'unpaid'], true)) {
            return false;
        }

        return null;
    }
}
