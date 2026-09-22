<?php

declare(strict_types=1);

/*
 * The ten Belgian provinces (ISO 3166-2:BE) with their NIS prefix ranges,
 * region and trilingual names. Unlike resources/data.php this file is
 * hand-maintained, but the content is stable by design: ISO codes, NIS ranges
 * (unchanged since the 1977 mergers, including the 2025 Tongeren-Borgloon
 * merger in the 73 range) and official province names.
 *
 * The Brussels-Capital Region is NOT a province and is therefore absent here;
 * it lives in resources/regions.php and is reached through Belgium::region().
 *
 * @return array<string, array{region:string,prefixes:list<string>,names:array{nl:string,fr:string,en:string}}>
 */
return [
    'BE-VAN' => [
        'region' => 'BE-VLG',
        'prefixes' => ['11', '12', '13'],
        'names' => ['nl' => 'Antwerpen', 'fr' => 'Anvers', 'en' => 'Antwerp'],
    ],
    'BE-VBR' => [
        'region' => 'BE-VLG',
        'prefixes' => ['23', '24'],
        'names' => ['nl' => 'Vlaams-Brabant', 'fr' => 'Brabant flamand', 'en' => 'Flemish Brabant'],
    ],
    'BE-VLI' => [
        'region' => 'BE-VLG',
        'prefixes' => ['70', '71', '72', '73'],
        'names' => ['nl' => 'Limburg', 'fr' => 'Limbourg', 'en' => 'Limburg'],
    ],
    'BE-VOV' => [
        'region' => 'BE-VLG',
        'prefixes' => ['41', '42', '43', '44', '45', '46'],
        'names' => ['nl' => 'Oost-Vlaanderen', 'fr' => 'Flandre orientale', 'en' => 'East Flanders'],
    ],
    'BE-VWV' => [
        'region' => 'BE-VLG',
        'prefixes' => ['31', '32', '33', '34', '35', '36', '37', '38'],
        'names' => ['nl' => 'West-Vlaanderen', 'fr' => 'Flandre occidentale', 'en' => 'West Flanders'],
    ],
    'BE-WBR' => [
        'region' => 'BE-WAL',
        'prefixes' => ['25'],
        'names' => ['nl' => 'Waals-Brabant', 'fr' => 'Brabant wallon', 'en' => 'Walloon Brabant'],
    ],
    'BE-WHT' => [
        'region' => 'BE-WAL',
        'prefixes' => ['51', '52', '53', '54', '55', '56', '57', '58'],
        'names' => ['nl' => 'Henegouwen', 'fr' => 'Hainaut', 'en' => 'Hainaut'],
    ],
    'BE-WLG' => [
        'region' => 'BE-WAL',
        'prefixes' => ['61', '62', '63', '64', '65', '66'],
        'names' => ['nl' => 'Luik', 'fr' => 'Liège', 'en' => 'Liège'],
    ],
    'BE-WLX' => [
        'region' => 'BE-WAL',
        'prefixes' => ['81', '82', '83', '84', '85'],
        'names' => ['nl' => 'Luxemburg', 'fr' => 'Luxembourg', 'en' => 'Luxembourg'],
    ],
    'BE-WNA' => [
        'region' => 'BE-WAL',
        'prefixes' => ['91', '92', '93', '94'],
        'names' => ['nl' => 'Namen', 'fr' => 'Namur', 'en' => 'Namur'],
    ],
];
