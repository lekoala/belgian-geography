<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use InvalidArgumentException;
use LeKoala\BelgianGeography\Exception\DataNotGenerated;

/**
 * Hand-maintained postal places (resources/places.php) that BeST does not
 * restitute through postname_*, such as 1020 Laeken in the City of Brussels.
 *
 * A supplement only names a locality of an existing BeST relation (postal code
 * + municipality): it never creates a relation, and it is inert as soon as BeST
 * publishes one of its names for that relation.
 *
 * @internal
 */
final class SupplementaryPlaces
{
    /** @return array<string, list<array{string, array<string,string|null>}>> */
    public static function clean(mixed $raw, string $placeFile): array
    {
        $invalid = sprintf('Belgian geography places at %s are invalid.', $placeFile);
        if (!is_array($raw)) {
            throw new DataNotGenerated($invalid);
        }

        $places = [];
        foreach ($raw as $code => $rows) {
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new DataNotGenerated($invalid);
            }
            foreach ($rows as $row) {
                $places[(string) $code][] = self::cleanRow($row, $invalid);
            }
        }

        return $places;
    }

    /**
     * Appends the supplements to the BeST places of the same postal code.
     *
     * @param array<string, list<array{string, array<string,string|null>}>> $postalPlaces
     * @param array<string, list<array{string, array<string,string|null>}>> $supplements
     * @return array<string, list<array{string, array<string,string|null>}>>
     */
    public static function merge(array $postalPlaces, array $supplements): array
    {
        foreach ($supplements as $code => $rows) {
            $code = (string) $code;
            foreach ($rows as [$nisCode, $names]) {
                $published = self::publishedNames($postalPlaces[$code] ?? [], $nisCode);
                if ($published === []) {
                    throw new InvalidArgumentException(sprintf(
                        'Supplementary place for postal code "%s" references municipality "%s", which BeST does not relate to it.',
                        $code,
                        $nisCode,
                    ));
                }
                if (array_intersect_key(self::keys($names), $published) === []) {
                    $postalPlaces[$code][] = [$nisCode, $names];
                }
            }
        }

        return $postalPlaces;
    }

    /** @return array{string, array<string,string>} */
    private static function cleanRow(mixed $row, string $invalid): array
    {
        if (!is_array($row) || !array_is_list($row) || count($row) !== 2) {
            throw new DataNotGenerated($invalid);
        }
        [$nisCode, $names] = $row;
        if (!is_string($nisCode) || !is_array($names) || $names === []) {
            throw new DataNotGenerated($invalid);
        }
        $cleanNames = [];
        foreach ($names as $locale => $name) {
            if (!is_string($name) || trim($name) === '') {
                throw new DataNotGenerated($invalid);
            }
            $cleanNames[(string) $locale] = $name;
        }

        return [$nisCode, $cleanNames];
    }

    /**
     * Normalized names BeST publishes for this municipality under a postal code;
     * empty when BeST does not relate them at all.
     *
     * @param list<array{string, array<string,string|null>}> $places
     * @return array<string, true>
     */
    private static function publishedNames(array $places, string $nisCode): array
    {
        $keys = [];
        foreach ($places as [$placeNisCode, $placeNames]) {
            if ($placeNisCode === $nisCode) {
                $keys += self::keys($placeNames);
            }
        }

        return $keys;
    }

    /**
     * @param array<string,string|null> $names
     * @return array<string, true>
     */
    private static function keys(array $names): array
    {
        $keys = [];
        foreach ((new LocalizedName($names))->aliases() as $name) {
            $keys[Normalizer::key($name)] = true;
        }

        return $keys;
    }
}
