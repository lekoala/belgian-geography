<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

/**
 * Display spelling of a locality name (see Belgium::placeLabel()).
 *
 * BeST publishes the main locality of a postal code in capitals ("HALLE",
 * "HERNE"). For display, the spelling of the municipality is reused when the
 * names are the same word, then the spelling of a former municipality merged
 * into it; any other capitalized name is shown in title case, acronyms aside.
 *
 * @internal
 */
final class PlaceLabel
{
    /** Capitalized BeST place names that are acronyms, not shouted names. */
    private const array ACRONYMS = ['SHAPE'];

    public static function label(PostalPlace $place, string $locale, ?Municipality $municipality): ?string
    {
        $name = $place->names->get($locale);
        if ($name === null) {
            return null;
        }

        $candidates = [$municipality?->names];
        if (mb_strtoupper($name) === $name) {
            $candidates = [...$candidates, ...($municipality->formerNames ?? [])];
        }
        foreach ($candidates as $names) {
            $candidate = $names?->get($locale);
            if ($candidate !== null && mb_strtolower($candidate) === mb_strtolower($name)) {
                return $candidate;
            }
        }

        if (mb_strtoupper($name) !== $name || in_array($name, self::ACRONYMS, true)) {
            return $name;
        }

        return self::titleCase($name);
    }

    /** Title case per word; words are separated by spaces, hyphens or apostrophes. */
    private static function titleCase(string $name): string
    {
        $parts = preg_split("/([\\s'-]+)/u", mb_strtolower($name), -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $name;
        }

        return implode('', array_map(
            static fn(string $part): string => mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1),
            $parts,
        ));
    }
}
