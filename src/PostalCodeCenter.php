<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use Closure;

/**
 * Picks the municipality whose center stands for a postal code (see
 * Belgium::centerForPostalCode()).
 *
 * @internal
 */
final class PostalCodeCenter
{
    /**
     * @param list<Municipality> $municipalities municipalities of the postal code
     * @param Closure(string): list<string> $nisCodesForName NIS codes a name resolves to
     * @param list<string> $preferredNames
     */
    public static function pick(
        PostalCode $postalCode,
        array $municipalities,
        Closure $nisCodesForName,
        array $preferredNames,
    ): ?Coordinates {
        $candidates = array_values(array_filter(
            $municipalities,
            static fn(Municipality $municipality): bool => $municipality->coordinates !== null,
        ));

        foreach ($preferredNames as $preferredName) {
            $nisCodes = $nisCodesForName($preferredName);
            foreach ($postalCode->places() as $place) {
                if ($place->names->matches($preferredName)) {
                    $nisCodes[] = $place->municipalityNisCode;
                }
            }
            foreach ($candidates as $municipality) {
                if (in_array($municipality->nisCode, $nisCodes, true)) {
                    return $municipality->coordinates;
                }
            }
        }

        return ($candidates[0] ?? null)?->coordinates;
    }
}
