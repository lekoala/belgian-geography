<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use LeKoala\BelgianGeography\Exception\DataNotGenerated;

/**
 * Loads the hand-maintained companion layers (resources/aliases.php,
 * resources/provinces.php and resources/regions.php), looking next to the
 * snapshot file first and falling back to the packaged resources.
 */
final class ReferenceData
{
    public const int CENTERS_SCHEMA_VERSION = 1;

    /**
     * Loads the generated centers companion (resources/centers.php): one
     * approximate municipality center per NIS code, as [lat, lon, weight]
     * where weight is the number of contributing BeST address points.
     *
     * @return array<string, array{0:float,1:float,2:int}>
     */
    public static function centers(string $dataFile, bool $usePackagedReferences = true): array
    {
        $centersFile = self::resolveCompanionFile($dataFile, 'centers.php', $usePackagedReferences);
        if ($centersFile === null || !is_file($centersFile)) {
            return [];
        }

        $invalid = sprintf('Belgian geography centers at %s are invalid.', $centersFile);
        $raw = require $centersFile;
        if (!is_array($raw) || ($raw['schema_version'] ?? null) !== self::CENTERS_SCHEMA_VERSION) {
            throw new DataNotGenerated($invalid);
        }

        $municipalities = $raw['municipalities'] ?? null;
        if (!is_array($municipalities)) {
            throw new DataNotGenerated($invalid);
        }

        $centers = [];
        foreach ($municipalities as $nisCode => $row) {
            if (!is_array($row) || !array_is_list($row) || count($row) !== 3) {
                throw new DataNotGenerated($invalid);
            }
            [$latitude, $longitude, $weight] = $row;
            $latitude = self::toFiniteFloat($latitude);
            $longitude = self::toFiniteFloat($longitude);
            if ($latitude === null || $longitude === null || !is_int($weight) || $weight < 1) {
                throw new DataNotGenerated($invalid);
            }
            $centers[(string) $nisCode] = [$latitude, $longitude, $weight];
        }

        return $centers;
    }

    private static function toFiniteFloat(mixed $value): ?float
    {
        if (is_int($value)) {
            return (float) $value;
        }
        if (is_float($value)) {
            return is_finite($value) ? $value : null;
        }

        return null;
    }

    /** @return array<string,string> */
    public static function aliases(string $dataFile, bool $usePackagedReferences = true): array
    {
        $aliasFile = self::resolveCompanionFile($dataFile, 'aliases.php', $usePackagedReferences);
        $rawAliases = $aliasFile === null ? [] : (require $aliasFile);
        if (!is_array($rawAliases)) {
            throw new DataNotGenerated(sprintf('Belgian geography aliases at %s are invalid.', $aliasFile));
        }

        $aliases = [];
        foreach ($rawAliases as $alias => $nisCode) {
            if (!is_string($nisCode)) {
                throw new DataNotGenerated(sprintf('Belgian geography aliases at %s are invalid.', $aliasFile));
            }
            $aliases[(string) $alias] = $nisCode;
        }

        return $aliases;
    }

    /**
     * @return array<string, array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}>
     */
    public static function provinces(string $dataFile, bool $usePackagedReferences = true): array
    {
        $provinceFile = self::resolveCompanionFile($dataFile, 'provinces.php', $usePackagedReferences);
        $raw = $provinceFile === null ? [] : (require $provinceFile);
        if (!is_array($raw)) {
            throw new DataNotGenerated(sprintf('Belgian geography provinces at %s are invalid.', $provinceFile));
        }

        $provinces = [];
        foreach ($raw as $iso => $row) {
            $provinces[(string) $iso] = self::cleanProvinceRow($row, (string) $provinceFile);
        }

        return $provinces;
    }

    /**
     * @return array<string, array{names:array{nl:string,fr:string,en:string}}>
     */
    public static function regions(string $dataFile, bool $usePackagedReferences = true): array
    {
        $regionFile = self::resolveCompanionFile($dataFile, 'regions.php', $usePackagedReferences);
        $raw = $regionFile === null ? [] : (require $regionFile);
        if (!is_array($raw)) {
            throw new DataNotGenerated(sprintf('Belgian geography regions at %s are invalid.', $regionFile));
        }

        $regions = [];
        foreach ($raw as $iso => $row) {
            $regions[(string) $iso] = self::cleanRegionRow($row, (string) $regionFile);
        }

        return $regions;
    }

    private static function resolveCompanionFile(string $dataFile, string $name, bool $usePackagedReferences): ?string
    {
        $nextToData = dirname($dataFile) . '/' . $name;
        if (is_file($nextToData)) {
            return $nextToData;
        }
        if (!$usePackagedReferences) {
            return null;
        }

        return dirname(__DIR__) . '/resources/' . $name;
    }

    /**
     * @return array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}
     */
    private static function cleanProvinceRow(mixed $row, string $provinceFile): array
    {
        $invalid = sprintf('Belgian geography provinces at %s are invalid.', $provinceFile);
        if (!is_array($row)) {
            throw new DataNotGenerated($invalid);
        }
        $region = $row['region'] ?? null;
        $prefixes = $row['prefixes'] ?? null;
        $names = $row['names'] ?? null;
        if (!is_string($region) || !is_array($prefixes) || $prefixes === [] || !is_array($names)) {
            throw new DataNotGenerated($invalid);
        }
        $cleanPrefixes = [];
        foreach ($prefixes as $prefix) {
            if (!is_string($prefix) && !is_int($prefix)) {
                throw new DataNotGenerated($invalid);
            }
            $cleanPrefixes[] = (string) $prefix;
        }

        return [
            'region' => $region,
            'prefixes' => $cleanPrefixes,
            'names' => self::cleanTrilingualNames($names, $invalid),
        ];
    }

    /**
     * @return array{names:array{nl:string,fr:string,en:string}}
     */
    private static function cleanRegionRow(mixed $row, string $regionFile): array
    {
        $invalid = sprintf('Belgian geography regions at %s are invalid.', $regionFile);
        if (!is_array($row)) {
            throw new DataNotGenerated($invalid);
        }
        $names = $row['names'] ?? null;
        if (!is_array($names)) {
            throw new DataNotGenerated($invalid);
        }

        return ['names' => self::cleanTrilingualNames($names, $invalid)];
    }

    /**
     * @param array<mixed> $names
     * @return array{nl:string,fr:string,en:string}
     */
    private static function cleanTrilingualNames(array $names, string $invalid): array
    {
        $nl = $names['nl'] ?? null;
        $fr = $names['fr'] ?? null;
        $en = $names['en'] ?? null;
        if (!is_string($nl) || !is_string($fr) || !is_string($en)) {
            throw new DataNotGenerated($invalid);
        }

        return ['nl' => $nl, 'fr' => $fr, 'en' => $en];
    }
}
