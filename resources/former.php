<?php

declare(strict_types=1);

/*
 * Former municipalities, hand-maintained, keyed by the NIS code of the
 * municipality that absorbed them.
 *
 * Each entry lists the names of a municipality that no longer exists since a
 * merger (Flemish mergers of 2019 and 2025, Bastogne/Bertogne in 2025). Only
 * absorbed names are listed: a former municipality whose name the new one kept
 * (Hasselt, Tielt, Lokeren, …) is already covered by BeST.
 *
 * These names are never current names of record. They are used for two things:
 * - search: municipalityByName('Borgloon') resolves to Tongeren-Borgloon. Like
 *   the search aliases, a former name never shadows a current BeST name;
 * - display: placeLabel() reuses their spelling for a BeST locality published
 *   in capitals (1540 "HERNE" is shown as "Herne").
 *
 * Names follow the same locale keys as the snapshot. A locale is only given when
 * the former municipality had a name of its own in it (Borgloon/Looz).
 *
 * Last reviewed against BeST snapshot: 2026-09-22.
 *
 * @return array<string, list<array<string,string>>> NIS code => former names
 */
return [
    // 2019 mergers
    '12041' => [['nl' => 'Puurs', 'fr' => 'Puers'], ['nl' => 'Sint-Amands']],
    '44083' => [['nl' => 'Nevele']],
    '44084' => [['nl' => 'Knesselare']],
    '44085' => [['nl' => 'Lovendegem'], ['nl' => 'Waarschoot'], ['nl' => 'Zomergem']],
    '45068' => [['nl' => 'Kruishoutem'], ['nl' => 'Zingem']],
    '72042' => [['nl' => 'Meeuwen-Gruitrode'], ['nl' => 'Opglabbeek']],
    '72043' => [['nl' => 'Neerpelt'], ['nl' => 'Overpelt']],
    // 2025 mergers
    '11002' => [['nl' => 'Borsbeek']],
    '23106' => [['nl' => 'Galmaarden', 'fr' => 'Gammerages'], ['nl' => 'Gooik'], ['nl' => 'Herne']],
    '37021' => [['nl' => 'Ruiselede']],
    '37022' => [['nl' => 'Meulebeke']],
    '44086' => [['nl' => 'Nazareth'], ['nl' => 'De Pinte']],
    '44087' => [['nl' => 'Wachtebeke']],
    '44088' => [['nl' => 'Merelbeke'], ['nl' => 'Melle']],
    '46029' => [['nl' => 'Moerbeke']],
    '46030' => [['nl' => 'Beveren'], ['nl' => 'Kruibeke'], ['nl' => 'Zwijndrecht']],
    '71071' => [['nl' => 'Tessenderlo'], ['nl' => 'Ham']],
    '71072' => [['nl' => 'Kortessem']],
    '73110' => [['nl' => 'Bilzen'], ['nl' => 'Hoeselt']],
    '73111' => [['nl' => 'Tongeren', 'fr' => 'Tongres', 'de' => 'Tongern'], ['nl' => 'Borgloon', 'fr' => 'Looz']],
    '82039' => [['fr' => 'Bertogne']],
];
