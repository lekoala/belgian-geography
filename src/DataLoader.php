<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use LeKoala\BelgianGeography\Exception\DataNotGenerated;

/**
 * Reads and validates the generated snapshot (resources/data.php) together
 * with the hand-maintained layers (resources/aliases.php,
 * resources/provinces.php and resources/regions.php).
 *
 * Snapshot records are tuples: a municipality is [region, names] and a
 * locality is [municipality NIS code, names]; locale keys inside names stay
 * explicit. This is an internal storage format, not a public contract.
 */
final class DataLoader
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @return array{
     *   meta: array<string, mixed>,
     *   municipalities: array<string, array{string, array<string,string|null>}>,
     *   postal_places: array<string, list<array{string, array<string,string|null>}>>,
     *   aliases: array<string,string>,
     *   provinces: array<string, array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}>,
     *   regions: array<string, array{names:array{nl:string,fr:string,en:string}}>
     * }
     */
    public static function load(string $file, bool $usePackagedReferences = true): array
    {
        if (!is_file($file)) {
            throw new DataNotGenerated(sprintf(
                'Belgian geography data not found at %s. Run "composer data:update" before packaging a release.',
                $file,
            ));
        }

        $data = require $file;
        if (!is_array($data)) {
            throw new DataNotGenerated(sprintf('Belgian geography data at %s is empty or invalid.', $file));
        }

        $schemaVersion = $data['schema_version'] ?? null;
        if ($schemaVersion !== self::SCHEMA_VERSION) {
            throw new DataNotGenerated(sprintf(
                'Belgian geography data at %s uses an unsupported schema version (expected %d).',
                $file,
                self::SCHEMA_VERSION,
            ));
        }

        $meta = $data['meta'] ?? [];
        $municipalities = $data['municipalities'] ?? null;
        $postalPlaces = $data['postal_places'] ?? null;
        if (!is_array($meta) || !is_array($municipalities) || $municipalities === [] || !is_array($postalPlaces)) {
            throw new DataNotGenerated(sprintf('Belgian geography data at %s is empty or invalid.', $file));
        }

        $cleanMeta = [];
        foreach ($meta as $key => $value) {
            $cleanMeta[(string) $key] = $value;
        }

        $cleanMunicipalities = self::cleanMunicipalities($municipalities, $file);

        return [
            'meta' => $cleanMeta,
            'municipalities' => $cleanMunicipalities,
            'postal_places' => self::cleanPostalPlaces($postalPlaces, $file, $cleanMunicipalities),
            'aliases' => ReferenceData::aliases($file, $usePackagedReferences),
            'provinces' => ReferenceData::provinces($file, $usePackagedReferences),
            'regions' => ReferenceData::regions($file, $usePackagedReferences),
        ];
    }

    /**
     * @param array<mixed> $municipalities
     * @return array<string, array{string, array<string,string|null>}>
     */
    private static function cleanMunicipalities(array $municipalities, string $file): array
    {
        $invalid = sprintf('Belgian geography data at %s is empty or invalid.', $file);
        $clean = [];
        foreach ($municipalities as $nisCode => $row) {
            if (!is_array($row) || !array_is_list($row) || count($row) !== 2) {
                throw new DataNotGenerated($invalid);
            }
            [$region, $names] = $row;
            if (!is_string($region) || !is_array($names)) {
                throw new DataNotGenerated($invalid);
            }
            $clean[(string) $nisCode] = [$region, self::cleanNames($names, $file)];
        }

        return $clean;
    }

    /**
     * A locality carries no region of its own: the region is a property of its
     * municipality. The loader only validates the reference here; Belgium derives
     * the region when building its indexes.
     *
     * @param array<mixed> $postalPlaces
     * @param array<string, array{string, array<string,string|null>}> $municipalities
     * @return array<string, list<array{string, array<string,string|null>}>>
     */
    private static function cleanPostalPlaces(array $postalPlaces, string $file, array $municipalities): array
    {
        $invalid = sprintf('Belgian geography data at %s is empty or invalid.', $file);
        $clean = [];
        foreach ($postalPlaces as $code => $rows) {
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new DataNotGenerated($invalid);
            }
            $places = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !array_is_list($row) || count($row) !== 2) {
                    throw new DataNotGenerated($invalid);
                }
                [$municipality, $names] = $row;
                if (!is_string($municipality) || !is_array($names)) {
                    throw new DataNotGenerated($invalid);
                }
                if (!array_key_exists($municipality, $municipalities)) {
                    throw new DataNotGenerated(sprintf(
                        'Belgian geography data at %s references unknown municipality "%s".',
                        $file,
                        $municipality,
                    ));
                }
                $places[] = [$municipality, self::cleanNames($names, $file)];
            }
            $clean[(string) $code] = $places;
        }

        return $clean;
    }

    /**
     * @param array<mixed> $names
     * @return array<string,string|null>
     */
    private static function cleanNames(array $names, string $file): array
    {
        $clean = [];
        foreach ($names as $locale => $name) {
            if (!is_string($name) && $name !== null) {
                throw new DataNotGenerated(sprintf('Belgian geography data at %s is empty or invalid.', $file));
            }
            $clean[(string) $locale] = $name;
        }

        return $clean;
    }
}
