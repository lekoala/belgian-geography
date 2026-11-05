<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use LeKoala\BelgianGeography\Exception\DataNotGenerated;

/**
 * Hand-maintained former (merged) municipalities (resources/former.php), keyed
 * by the NIS code of the municipality that absorbed them. Belgium attaches them
 * to that municipality (Municipality::$formerNames) and indexes them for search.
 *
 * @internal
 */
final class FormerMunicipalities
{
    /** @return array<string, list<array<string,string>>> */
    public static function clean(mixed $raw, string $formerFile): array
    {
        $invalid = sprintf('Belgian geography former municipalities at %s are invalid.', $formerFile);
        if (!is_array($raw)) {
            throw new DataNotGenerated($invalid);
        }

        $former = [];
        foreach ($raw as $nisCode => $entries) {
            if (!is_array($entries) || !array_is_list($entries)) {
                throw new DataNotGenerated($invalid);
            }
            foreach ($entries as $names) {
                $former[(string) $nisCode][] = self::cleanNames($names, $invalid);
            }
        }

        return $former;
    }

    /**
     * Former names as search keys, in the format of resources/aliases.php.
     *
     * @param array<string, list<array<string,string>>> $former
     * @return array<string,string> normalized former name => NIS code
     */
    public static function searchKeys(array $former): array
    {
        $keys = [];
        foreach ($former as $nisCode => $entries) {
            foreach ($entries as $names) {
                foreach ($names as $name) {
                    $key = Normalizer::key($name);
                    if ($key !== '') {
                        $keys[$key] ??= (string) $nisCode;
                    }
                }
            }
        }

        return $keys;
    }

    /** @return array<string,string> */
    private static function cleanNames(mixed $names, string $invalid): array
    {
        if (!is_array($names) || $names === []) {
            throw new DataNotGenerated($invalid);
        }
        $clean = [];
        foreach ($names as $locale => $name) {
            if (!is_string($name) || trim($name) === '') {
                throw new DataNotGenerated($invalid);
            }
            $clean[(string) $locale] = $name;
        }

        return $clean;
    }
}
