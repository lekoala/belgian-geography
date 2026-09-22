<?php

declare(strict_types=1);

/*
 * Minimal common-exonym alias layer, keyed by Normalizer::key() output.
 *
 * Sourcing rule: an entry is kept ONLY when it is attested as a current
 * alternate name (EN Wikipedia: list of cities, language-facility
 * municipalities, Wallonia municipalities — native name per region: nl in
 * Flanders, fr in Wallonia, co-official fr/nl in Brussels) AND missing from
 * the generated BeST snapshot (verified with tools/check-alias-coverage.php).
 * Archaic spellings (aerschot-era variants, blankenberghe, rethy, …) are
 * deliberately excluded: they belong to consuming applications.
 *
 * Alias values are NIS codes, never names: identity stays on the NIS code.
 * At load time an alias is registered only when its key is NOT already
 * indexed from BeST data, so current BeST names always win over aliases.
 *
 * Exception: 'brussels' is the international (English) form of Bruxelles/
 * Brussel (NIS 21004, City of Brussels) — not one of the 18 other Brussels
 * municipalities.
 *
 * Last reviewed against BeST snapshot: 2026-09-22.
 *
 * @return array<string, string> normalized alias => NIS code
 */
return [
    'aerschot' => '24001', // fr: Aarschot
    'antwerp' => '11002', // en: Antwerpen
    'arel' => '81001', // de: Arlon
    'bleiberg' => '63088', // de: Plombières
    'blieberg' => '63088', // nl: Plombières
    'brussels' => '21004', // en/intl: Bruxelles/Brussel (City of Brussels)
    'crainhem' => '23099', // fr (uncommon): Kraainem
    'ghent' => '44021', // en: Gent
    'malmund' => '63049', // de (uncommon): Malmedy
    'tongres' => '73111', // fr: Tongeren-Borgloon (merged 2025)
    'welkenraat' => '63084', // nl: Welkenraedt
    'welkenrath' => '63084', // de: Welkenraedt
];
