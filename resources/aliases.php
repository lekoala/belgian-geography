<?php

declare(strict_types=1);

/*
 * Search-alias layer, keyed by Normalizer::key() output.
 *
 * These are SEARCH ALIASES, never municipality names: identity and current
 * names come from the BeST snapshot alone. Statbel 2025, for instance,
 * publishes 24001 as Aarschot in NL and FR, and 73111 as Tongeren-Borgloon /
 * Tongres-Looz, so entries such as 'aerschot' or 'tongres' are lookup helpers,
 * not names of record.
 *
 * Sourcing rule: an entry is kept ONLY when it is a plausible search spelling
 * (EN Wikipedia: list of cities, language-facility municipalities, Wallonia
 * municipalities — native name per region: nl in Flanders, fr in Wallonia,
 * co-official fr/nl in Brussels — plus the usual short forms of Brussels
 * municipalities) AND missing from the generated BeST snapshot
 * (verified with tools/check-alias-coverage.php). Archaic spellings
 * (aerschot-era variants, blankenberghe, rethy, …) are deliberately excluded:
 * they belong to consuming applications.
 *
 * Alias values are NIS codes, never names: identity stays on the NIS code.
 * At load time an alias is registered only when its key is NOT already indexed
 * from BeST data, so current BeST names always win; an alias already covered by
 * BeST is simply inert.
 *
 * Localities are not aliases: Laeken, Neder-Over-Heembeek or Haren are postal
 * places of the City of Brussels and live in resources/places.php.
 *
 * Exception: 'brussels' is the international (English) form of Bruxelles/
 * Brussel (NIS 21004, City of Brussels) — not one of the 18 other Brussels
 * municipalities.
 *
 * Last reviewed against BeST snapshot: 2026-09-22.
 *
 * @return array<string, string> normalized search alias => NIS code
 */
return [
    'aerschot' => '24001', // search alias for Aarschot
    'antwerp' => '11002', // search alias for Antwerpen
    'arel' => '81001', // search alias for Arlon
    'bleiberg' => '63088', // search alias for Plombières
    'blieberg' => '63088', // search alias for Plombières
    'brussels' => '21004', // intl. search alias for the City of Brussels (Bruxelles/Brussel)
    'crainhem' => '23099', // search alias for Kraainem
    'ghent' => '44021', // search alias for Gent
    'malmund' => '63049', // search alias for Malmedy
    'molenbeek' => '21012', // short form of Molenbeek-Saint-Jean / Sint-Jans-Molenbeek
    'saintjosse' => '21014', // short form of Saint-Josse-ten-Noode
    'sintjoost' => '21014', // short form of Sint-Joost-ten-Node
    'tongres' => '73111', // search alias for Tongeren-Borgloon (merged 2025)
    'welkenraat' => '63084', // search alias for Welkenraedt
    'welkenrath' => '63084', // search alias for Welkenraedt
];
