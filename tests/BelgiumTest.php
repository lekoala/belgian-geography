<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\Tools\Generator;
use PHPUnit\Framework\TestCase;

final class BelgiumTest extends TestCase
{
    public function test_it_resolves_current_multilingual_municipality_names(): void
    {
        $belgium = $this->belgium();

        $this->assertSame('62063', $belgium->municipalityByName('Liège')?->nisCode);
        $this->assertSame('62063', $belgium->municipalityByName('liege')?->nisCode);
        $this->assertSame('62063', $belgium->municipalityByName('Luik')?->nisCode);
        $this->assertSame('62063', $belgium->municipalityByName('Luttich')?->nisCode);
        $this->assertSame('11002', $belgium->municipalityByName('Anvers')?->nisCode);
    }

    public function test_postal_code_can_map_to_multiple_municipalities(): void
    {
        $belgium = $this->belgium();
        $municipalities = $belgium->municipalitiesForPostalCode('1040');

        $this->assertSame(
            ['21004', '21005'],
            array_map(static fn($municipality): string => $municipality->nisCode, $municipalities),
        );
    }

    public function test_it_exposes_postal_places_without_promoting_them_to_municipalities(): void
    {
        $belgium = $this->belgium();
        $jambes = $belgium->postalPlacesByName('Jambes');

        $this->assertCount(1, $jambes);
        $this->assertSame('5100', $jambes[0]->postalCode);
        $this->assertSame('92094', $jambes[0]->municipalityNisCode);
        $this->assertSame('Namur', $belgium->municipality('92094')?->name('fr'));
        $this->assertSame('Namen', $belgium->municipality('92094')?->name('nl'));
    }

    public function test_it_splits_combined_postal_labels_into_localities(): void
    {
        $belgium = $this->belgium();
        $koolkerke = $belgium->postalPlacesByName('Koolkerke');

        $this->assertCount(1, $koolkerke);
        $this->assertSame('8000', $koolkerke[0]->postalCode);
        $this->assertSame('31005', $koolkerke[0]->municipalityNisCode);

        $municipalities = $belgium->municipalitiesForPostalCode('8000');
        $this->assertSame(
            ['31005'],
            array_map(static fn($municipality): string => $municipality->nisCode, $municipalities),
        );
    }

    public function test_it_keeps_the_translations_of_a_single_locality_together(): void
    {
        $belgium = $this->belgium();
        $brussels = $belgium->postalPlacesByName('Bruxelles');

        $this->assertNotEmpty($brussels);
        $this->assertSame('BRUXELLES', $brussels[0]->name('fr'));
        $this->assertSame('BRUSSEL', $brussels[0]->name('nl'));
    }

    public function test_it_does_not_duplicate_places_whose_names_normalize_alike(): void
    {
        $belgium = new Belgium([
            'municipalities' => [
                '63013' => ['BE-WAL', ['nl' => 'Bütgenbach', 'fr' => 'Butgenbach', 'de' => 'Bütgenbach']],
            ],
            'postal_places' => [
                '4750' => [
                    ['63013', ['nl' => 'Bütgenbach', 'fr' => 'Butgenbach', 'de' => 'Bütgenbach']],
                ],
            ],
        ]);

        $this->assertCount(1, $belgium->postalPlacesByName('Butgenbach'));
        $this->assertCount(1, $belgium->postalPlacesByName('Bütgenbach'));
    }

    public function test_it_does_not_duplicate_snapshot_localities(): void
    {
        $belgium = Belgium::load();

        // "Bütgenbach" (nl/de) and "Butgenbach" (fr) normalize to the same key.
        $this->assertCount(1, $belgium->postalPlacesByName('Butgenbach'));
        $this->assertCount(1, $belgium->postalPlacesByName('Bütgenbach'));
    }

    public function test_postal_code_lookup_is_strict_after_trim(): void
    {
        $belgium = $this->belgium();

        $this->assertNull($belgium->postalCode('abc1000xyz'));
        $this->assertNull($belgium->postalCode('100'));
        $this->assertNull($belgium->postalCode('10000'));
        $this->assertNull($belgium->postalCode(''));
        $this->assertSame('1040', $belgium->postalCode(' 1040 ')?->code);
        $this->assertSame('1040', $belgium->postalCode(1040)?->code);
    }

    private function belgium(): Belgium
    {
        $generator = new Generator();
        foreach ([
            'BE-VLG' => __DIR__ . '/Fixtures/Flanders_addresses.csv',
            'BE-BRU' => __DIR__ . '/Fixtures/Brussels_addresses.csv',
            'BE-WAL' => __DIR__ . '/Fixtures/Wallonia_addresses.csv',
        ] as $region => $file) {
            $stream = fopen($file, 'rb');
            $this->assertIsResource($stream);
            $generator->ingestAddressCsv($stream, $region);
            fclose($stream);
        }

        return new Belgium($generator->build());
    }
}
