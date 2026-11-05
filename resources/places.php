<?php

declare(strict_types=1);

/*
 * Supplementary postal places, hand-maintained, keyed by postal code.
 *
 * These are real current localities that BeST does not restitute through its
 * postname_* fields: for 1020, 1120 and 1130 BeST only publishes
 * BRUXELLES/BRUSSEL, while the City of Brussels and bpost both identify these
 * postal areas as Laeken, Neder-Over-Heembeek and Haren (former municipalities
 * merged into the City of Brussels in 1921).
 *
 * Rows use the same [municipality NIS code, names] tuple as the snapshot's
 * postal_places and become ordinary PostalPlace objects, next to the BeST
 * places of the same postal code. They are never municipalities:
 * municipalityByName('Laeken') stays null.
 *
 * A row may also complete a BeST place with the names BeST leaves empty: the
 * French names of Flemish localities (8958 Loker / Locre) and the Dutch names of
 * Walloon ones (7850 Petit-Enghien / Lettelingen). Such a row repeats the BeST
 * name it completes, which identifies the place.
 *
 * Rules enforced at load time:
 * - the postal code / municipality relation must already exist in BeST: a
 *   supplement only names a locality, it never creates a relation;
 * - a row sharing a name with a BeST place of that relation completes it: it
 *   only fills empty locales and never overrides a BeST name, so a future BeST
 *   fix makes it inert.
 *
 * Only localities with a postal identity of their own belong here; this is not
 * a list of neighbourhoods or historical sections.
 *
 * Last reviewed against BeST snapshot: 2026-11-05.
 *
 * @return array<string, list<array{string, array<string,string>}>>
 */
return [
    '1020' => [
        ['21004', ['nl' => 'Laken', 'fr' => 'Laeken']],
    ],
    '1120' => [
        ['21004', ['nl' => 'Neder-Over-Heembeek', 'fr' => 'Neder-Over-Heembeek']],
    ],
    '1130' => [
        ['21004', ['nl' => 'Haren', 'fr' => 'Haren']],
    ],
    // Enghien: Dutch names of its localities.
    '7850' => [
        ['51067', ['fr' => 'Marcq', 'nl' => 'Mark']],
        ['51067', ['fr' => 'Petit-Enghien', 'nl' => 'Lettelingen']],
    ],
    // Heuvelland: French names of its localities.
    '8950' => [
        ['33039', ['nl' => 'Nieuwkerke', 'fr' => 'Neuve-Église']],
    ],
    '8951' => [
        ['33039', ['nl' => 'Dranouter', 'fr' => 'Dranoutre']],
    ],
    '8952' => [
        ['33039', ['nl' => 'Wulvergem', 'fr' => 'Wulverghem']],
    ],
    '8953' => [
        ['33039', ['nl' => 'Wijtschate', 'fr' => 'Wytschaete']],
    ],
    '8954' => [
        ['33039', ['nl' => 'Westouter', 'fr' => 'Westoutre']],
    ],
    '8958' => [
        ['33039', ['nl' => 'Loker', 'fr' => 'Locre']],
    ],
];
