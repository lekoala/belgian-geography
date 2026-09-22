<?php

declare(strict_types=1);

/*
 * The three Belgian regions (ISO 3166-2:BE) with their trilingual names.
 * Hand-maintained and stable by design.
 *
 * Regions are deliberately separated from provinces: the Brussels-Capital
 * Region is an administrative region, not a province, so it must not appear
 * in resources/provinces.php. Together, the three regions cover the country.
 *
 * @return array<string, array{names:array{nl:string,fr:string,en:string}}>
 */
return [
    'BE-VLG' => [
        'names' => ['nl' => 'Vlaanderen', 'fr' => 'Flandre', 'en' => 'Flanders'],
    ],
    'BE-WAL' => [
        'names' => ['nl' => 'Wallonië', 'fr' => 'Wallonie', 'en' => 'Wallonia'],
    ],
    'BE-BRU' => [
        'names' => ['nl' => 'Brussel', 'fr' => 'Bruxelles', 'en' => 'Brussels'],
    ],
];
