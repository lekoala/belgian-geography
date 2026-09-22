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
 * Rules enforced at load time:
 * - the postal code / municipality relation must already exist in BeST: a
 *   supplement only names a locality, it never creates a relation;
 * - a supplement whose name is already published by BeST for that postal code
 *   and municipality is inert, so a future BeST fix makes it disappear.
 *
 * Only localities with a postal identity of their own belong here; this is not
 * a list of neighbourhoods or historical sections.
 *
 * Last reviewed against BeST snapshot: 2026-09-22.
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
];
