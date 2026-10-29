<?php

/**
 * Finds a place in the geographies branch by name or by id.
 *
 * The import form names one place for the whole file; a served list names one
 * on every row. Both have to land on the same ids, so both ask here.
 *
 * Barangay names repeat across the region — there is a Poblacion in most
 * municipalities — so a level is only ever searched inside the one above it.
 */
class GeographyIndex
{
    /** @var array<string, array{id: string, name: string}> keyed by level|parent|name */
    private array $byName = [];

    /** @var array<string, array<string, mixed>> the branch as it came */
    private array $places;

    /** @var array<string, array<string, string>|null> places already resolved */
    private array $resolved = [];

    /**
     * @param array<string, mixed> $geographies the geographies branch
     */
    public function __construct(array $geographies)
    {
        $this->places = array_filter($geographies, 'is_array');

        foreach ($this->places as $id => $place) {
            $level = (string) ($place['level'] ?? '');
            $name = (string) ($place['name'] ?? '');

            if ($level === '' || $name === '') {
                continue;
            }

            $key = $this->keyFor($level, (string) ($place['parentId'] ?? ''), $name);
            $this->byName[$key] = ['id' => (string) $id, 'name' => $name];
        }
    }

    /**
     * One place, by its id or its name, within the level it belongs to.
     *
     * @return array{0: string, 1: string}|null the id and the name as stored
     */
    public function find(string $level, string $wanted, ?string $parentId): ?array
    {
        $wanted = trim($wanted);

        if ($wanted === '') {
            return null;
        }

        // An id is accepted as readily as a name, because the dashboard passes
        // ids back where the form passes what somebody typed.
        $place = $this->places[$wanted] ?? null;

        if (is_array($place)
            && ($place['level'] ?? '') === $level
            && ($parentId === null || ($place['parentId'] ?? '') === $parentId)) {
            return [$wanted, (string) ($place['name'] ?? '')];
        }

        $found = $this->byName[$this->keyFor($level, (string) $parentId, $wanted)] ?? null;

        if ($found !== null) {
            return [$found['id'], $found['name']];
        }

        // Only a search with no parent may fall back to looking everywhere,
        // which is what lets a province be found without one.
        if ($parentId !== null) {
            return null;
        }

        foreach ($this->places as $id => $candidate) {
            if (($candidate['level'] ?? '') === $level
                && strcasecmp((string) ($candidate['name'] ?? ''), $wanted) === 0) {
                return [(string) $id, (string) $candidate['name']];
            }
        }

        return null;
    }

    /**
     * The three levels at once, each looked up inside the one above it.
     *
     * A served list repeats the same place on row after row, so the answer is
     * kept rather than worked out thousands of times.
     *
     * @return array<string, string>|null the six id and name fields, or null
     */
    public function resolve(string $province, string $municipality, string $barangay): ?array
    {
        $key = strtolower($province . '|' . $municipality . '|' . $barangay);

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        return $this->resolved[$key] = $this->lookUp($province, $municipality, $barangay);
    }

    /**
     * @return array<string, string>|null
     */
    private function lookUp(string $province, string $municipality, string $barangay): ?array
    {
        $foundProvince = $this->find('province', $province, null);

        if ($foundProvince === null) {
            return null;
        }

        $foundMunicipality = $this->find('municipality', $municipality, $foundProvince[0]);

        if ($foundMunicipality === null) {
            return null;
        }

        $foundBarangay = $this->find('barangay', $barangay, $foundMunicipality[0]);

        if ($foundBarangay === null) {
            return null;
        }

        return [
            'provinceId' => $foundProvince[0],
            'provinceName' => $foundProvince[1],
            'municipalityId' => $foundMunicipality[0],
            'municipalityName' => $foundMunicipality[1],
            'barangayId' => $foundBarangay[0],
            'barangayName' => $foundBarangay[1],
        ];
    }

    private function keyFor(string $level, string $parentId, string $name): string
    {
        return $level . '|' . $parentId . '|' . strtolower(trim($name));
    }
}
