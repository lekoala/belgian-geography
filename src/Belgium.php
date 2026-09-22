<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use InvalidArgumentException;

final class Belgium
{
    /** @var array<string, Municipality> */
    private array $municipalities = [];

    /** @var array<string, PostalCode> */
    private array $postalCodes = [];

    /** @var array<string, list<string>> normalized name => NIS codes */
    private array $municipalityNameIndex = [];

    /** @var array<string, list<PostalPlace>> normalized name => places */
    private array $placeNameIndex = [];

    /** @var array<string, list<string>> NIS => postal codes */
    private array $municipalityPostalIndex = [];

    /** @var array<string, Province> ISO 3166-2 code => province */
    private array $provinces = [];

    /** @var array<string, Region> ISO 3166-2 code => region */
    private array $regions = [];

    private ?MunicipalityCoordinates $municipalityCoordinates = null;

    /** @var array<string, string> NIS prefix (2 digits) => ISO 3166-2 code */
    private array $provincePrefixIndex = [];

    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param array{
     *   meta?: array<string, mixed>,
     *   municipalities?: array<string, array{string, array<string,string|null>}>,
     *   postal_places?: array<string, list<array{string, array<string,string|null>}>>,
     *   centers?: array<string, array{0:float,1:float,2:int}>,
     *   aliases?: array<string,string>,
     *   provinces?: array<string, array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}>,
     *   regions?: array<string, array{names:array{nl:string,fr:string,en:string}}>
     * } $data
     */
    public function __construct(array $data)
    {
        $this->metadata = $data['meta'] ?? [];
        $this->indexMunicipalities($data['municipalities'] ?? [], $data['centers'] ?? []);
        $this->indexAliases($data['aliases'] ?? []);
        $this->indexProvinces($data['provinces'] ?? []);
        $this->indexRegions($data['regions'] ?? []);
        $this->indexPostalPlaces($data['postal_places'] ?? []);
        $this->finalizeIndexes();
    }

    public static function load(?string $file = null, bool $usePackagedReferences = true): self
    {
        $file ??= dirname(__DIR__) . '/resources/data.php';

        return new self(DataLoader::load($file, $usePackagedReferences));
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function municipality(string $nisCode): ?Municipality
    {
        return $this->municipalities[$nisCode] ?? null;
    }

    /** @return list<Municipality> */
    public function municipalities(): array
    {
        return array_values($this->municipalities);
    }

    /** @return list<Municipality> */
    public function municipalitiesForRegion(string $region): array
    {
        return array_values(array_filter(
            $this->municipalities,
            static fn(Municipality $municipality): bool => $municipality->region === $region,
        ));
    }

    /**
     * Resolve an exact current municipality name in any available language.
     * Returns null when there is no match or when the normalized name is ambiguous.
     */
    public function municipalityByName(string $name): ?Municipality
    {
        $matches = $this->municipalitiesByName($name);

        return count($matches) === 1 ? $matches[0] : null;
    }

    /** @return list<Municipality> */
    public function municipalitiesByName(string $name): array
    {
        $nisCodes = $this->municipalityNameIndex[Normalizer::key($name)] ?? [];

        return array_values(array_filter(array_map($this->municipality(...), $nisCodes)));
    }

    public function postalCode(string|int $code): ?PostalCode
    {
        $code = trim((string) $code);
        if (preg_match('/^\d{4}$/', $code) !== 1) {
            return null;
        }

        return $this->postalCodes[$code] ?? null;
    }

    /** @return list<PostalCode> */
    public function postalCodes(): array
    {
        $codes = $this->postalCodes;
        ksort($codes, SORT_STRING);

        return array_values($codes);
    }

    /** @return list<PostalPlace> */
    public function postalPlacesByName(string $name): array
    {
        return $this->placeNameIndex[Normalizer::key($name)] ?? [];
    }

    /**
     * Display label for a locality. When the locality name is the same word as
     * its municipality name in the same locale (case aside), the municipality
     * spelling is reused for presentation; the source spelling stays available
     * through PostalPlace::name()/displayName().
     */
    public function placeLabel(PostalPlace $place, string $locale, string ...$fallbacks): ?string
    {
        $resolved = null;
        foreach ([$locale, ...$fallbacks] as $candidate) {
            if ($place->names->get($candidate) !== null) {
                $resolved = $candidate;
                break;
            }
        }
        $resolved ??= array_key_first($place->names->all());
        if ($resolved === null) {
            return null;
        }

        $name = $place->names->get($resolved);
        $municipality = $this->municipalities[$place->municipalityNisCode] ?? null;
        $municipalityName = $municipality?->name($resolved);
        if ($name !== null && $municipalityName !== null && strcasecmp($name, $municipalityName) === 0) {
            return $municipalityName;
        }

        return $name;
    }

    /** @return list<Municipality> */
    public function municipalitiesForPostalCode(string|int $code, ?string $region = null): array
    {
        $postalCode = $this->postalCode($code);
        if ($postalCode === null) {
            return [];
        }

        $municipalities = array_values(array_filter(array_map(
            $this->municipality(...),
            $postalCode->municipalityNisCodes(),
        )));
        if ($region === null) {
            return $municipalities;
        }

        return array_values(array_filter(
            $municipalities,
            static fn(Municipality $municipality): bool => $municipality->region === $region,
        ));
    }

    /** @return list<PostalCode> */
    public function postalCodesForMunicipality(string $nisCode): array
    {
        return array_values(array_filter(array_map(
            $this->postalCode(...),
            $this->municipalityPostalIndex[$nisCode] ?? [],
        )));
    }

    public function province(string $isoCode): ?Province
    {
        return $this->provinces[strtoupper($isoCode)] ?? null;
    }

    /** @return list<Province> */
    public function provinces(): array
    {
        return array_values($this->provinces);
    }

    public function provinceForMunicipality(string $nisCode): ?Province
    {
        if (!array_key_exists($nisCode, $this->municipalities)) {
            return null;
        }
        $isoCode = $this->provincePrefixIndex[substr($nisCode, 0, 2)] ?? null;

        return $isoCode === null ? null : $this->provinces[$isoCode];
    }

    /** @return list<Municipality> */
    public function municipalitiesForProvince(string $isoCode): array
    {
        $province = $this->province($isoCode);
        if ($province === null) {
            return [];
        }

        return array_values(array_filter(
            $this->municipalities,
            fn(Municipality $municipality): bool => (
                $this->provinceForMunicipality($municipality->nisCode) === $province
            ),
        ));
    }

    /** @return list<PostalCode> */
    public function postalCodesForProvince(string $isoCode): array
    {
        return $this->postalCodesForMunicipalities($this->municipalitiesForProvince($isoCode));
    }

    public function region(string $isoCode): ?Region
    {
        return $this->regions[strtoupper($isoCode)] ?? null;
    }

    /** @return list<Region> */
    public function regions(): array
    {
        return array_values($this->regions);
    }

    public function regionForMunicipality(string $nisCode): ?Region
    {
        $municipality = $this->municipalities[$nisCode] ?? null;
        if ($municipality === null) {
            return null;
        }

        return $this->regions[$municipality->region] ?? null;
    }

    /** @return list<PostalCode> */
    public function postalCodesForRegion(string $region): array
    {
        return $this->postalCodesForMunicipalities($this->municipalitiesForRegion($region));
    }

    /**
     * @param array<string, array{string, array<string,string|null>}> $municipalities
     * @param array<string, array{0:float,1:float,2:int}> $centers
     */
    private function indexMunicipalities(array $municipalities, array $centers): void
    {
        $coordinatesIndex = [];
        foreach ($municipalities as $nisCode => $row) {
            [$region, $names] = $row;
            $nisCode = (string) $nisCode;
            $coordinates = null;
            if (array_key_exists($nisCode, $centers)) {
                $center = $centers[$nisCode];
                $coordinates = new Coordinates($center[0], $center[1]);
                $coordinatesIndex[$nisCode] = [$coordinates, $center[2], $region];
            }
            $municipality = new Municipality($nisCode, $region, new LocalizedName($names), $coordinates);
            $this->municipalities[$municipality->nisCode] = $municipality;

            foreach ($municipality->names->aliases() as $alias) {
                $key = Normalizer::key($alias);
                if ($key !== '') {
                    $this->municipalityNameIndex[$key][] = $municipality->nisCode;
                }
            }
        }
        $this->municipalityCoordinates = new MunicipalityCoordinates($coordinatesIndex);
    }

    /** @param array<string,string> $aliases */
    private function indexAliases(array $aliases): void
    {
        foreach ($aliases as $alias => $nisCode) {
            if ($alias === '' || Normalizer::key($alias) !== $alias) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid alias key "%s": keys must be pre-normalized.',
                    $alias,
                ));
            }
            if (!array_key_exists($nisCode, $this->municipalities)) {
                throw new InvalidArgumentException(sprintf('Unknown NIS code "%s" for alias "%s".', $nisCode, $alias));
            }
            // Current BeST names always win: an alias never shadows an indexed name.
            $this->municipalityNameIndex[$alias] ??= [$nisCode];
        }
    }

    /**
     * @param array<string, array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}> $provinces
     */
    private function indexProvinces(array $provinces): void
    {
        foreach ($provinces as $isoCode => $row) {
            $isoCode = (string) $isoCode;
            $this->provinces[$isoCode] = new Province(
                $isoCode,
                $row['region'],
                $row['names'],
                $this->municipalityCoordinates?->forPrefixes($row['prefixes']),
            );
            foreach ($row['prefixes'] as $prefix) {
                $this->provincePrefixIndex[$prefix] ??= $isoCode;
            }
        }
    }

    /** @param array<string, array{names:array{nl:string,fr:string,en:string}}> $regions */
    private function indexRegions(array $regions): void
    {
        foreach ($regions as $isoCode => $row) {
            $isoCode = (string) $isoCode;
            $this->regions[$isoCode] = new Region(
                $isoCode,
                $row['names'],
                $this->municipalityCoordinates?->forRegion($isoCode),
            );
        }
    }

    /**
     * A locality has no region of its own: it is derived from its municipality.
     *
     * @param array<string, list<array{string, array<string,string|null>}>> $postalPlaces
     */
    private function indexPostalPlaces(array $postalPlaces): void
    {
        foreach ($postalPlaces as $code => $rows) {
            $places = [];
            foreach ($rows as $row) {
                [$nisCode, $names] = $row;
                $municipality = $this->municipalities[$nisCode] ?? null;
                if ($municipality === null) {
                    throw new InvalidArgumentException(sprintf(
                        'Unknown municipality "%s" for postal code "%s".',
                        $nisCode,
                        (string) $code,
                    ));
                }
                $place = new PostalPlace(
                    (string) $code,
                    $municipality->nisCode,
                    $municipality->region,
                    new LocalizedName($names),
                );
                $places[] = $place;
                $this->municipalityPostalIndex[$place->municipalityNisCode][] = (string) $code;
                $this->indexPlaceNames($place);
            }
            $this->postalCodes[(string) $code] = new PostalCode((string) $code, $places);
        }
    }

    private function indexPlaceNames(PostalPlace $place): void
    {
        // Several names of the same locality may normalize to the same key
        // ("Bütgenbach"/"Butgenbach"); index each key at most once per place.
        $keys = [];
        foreach ($place->names->aliases() as $alias) {
            $key = Normalizer::key($alias);
            if ($key !== '') {
                $keys[$key] = true;
            }
        }
        foreach (array_keys($keys) as $key) {
            $this->placeNameIndex[$key][] = $place;
        }
    }

    private function finalizeIndexes(): void
    {
        foreach ($this->municipalityNameIndex as &$nisCodes) {
            $nisCodes = array_values(array_unique($nisCodes));
        }
        unset($nisCodes);

        foreach ($this->municipalityPostalIndex as &$codes) {
            $codes = array_values(array_unique($codes));
            sort($codes, SORT_STRING);
        }
        unset($codes);
    }

    /**
     * @param list<Municipality> $municipalities
     * @return list<PostalCode>
     */
    private function postalCodesForMunicipalities(array $municipalities): array
    {
        $codes = [];
        foreach ($municipalities as $municipality) {
            foreach ($this->municipalityPostalIndex[$municipality->nisCode] ?? [] as $code) {
                $codes[$code] = true;
            }
        }
        ksort($codes, SORT_STRING);

        return array_values(array_filter(array_map($this->postalCode(...), array_keys($codes))));
    }
}
