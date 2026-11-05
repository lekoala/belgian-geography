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
 * + municipality): it never creates a relation. When it shares a name with a
 * BeST place of that relation, it is the same locality: the supplement only
 * fills the locales BeST leaves empty (8958 Loker gets its French name Locre)
 * and never overrides a BeST name. It is inert once BeST publishes all of them.
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
                $related = false;
                $match = null;
                $keys = self::keys($names);
                foreach ($postalPlaces[$code] ?? [] as $index => [$placeNisCode, $placeNames]) {
                    if ($placeNisCode !== $nisCode) {
                        continue;
                    }
                    $related = true;
                    if ($match === null && array_intersect_key($keys, self::keys($placeNames)) !== []) {
                        $match = $index;
                    }
                }
                if (!$related) {
                    throw new InvalidArgumentException(sprintf(
                        'Supplementary place for postal code "%s" references municipality "%s", which BeST does not relate to it.',
                        $code,
                        $nisCode,
                    ));
                }
                if ($match === null) {
                    $postalPlaces[$code][] = [$nisCode, $names];
                    continue;
                }
                // Same locality as a BeST place: only fill the locales BeST leaves empty.
                [$placeNisCode, $placeNames] = $postalPlaces[$code][$match];
                foreach ($names as $locale => $name) {
                    $published = $placeNames[$locale] ?? null;
                    if ($published === null || trim($published) === '') {
                        $placeNames[$locale] = $name;
                    }
                }
                $postalPlaces[$code][$match] = [$placeNisCode, $placeNames];
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
