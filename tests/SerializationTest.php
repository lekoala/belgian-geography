<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

final class SerializationTest extends TestCase
{
    public function test_municipality_serializes_its_names(): void
    {
        $belgium = Belgium::load();
        $municipality = $belgium->municipality('92094');
        $this->assertNotNull($municipality);

        $array = $municipality->toArray();
        $this->assertSame('92094', $array['nisCode']);
        $this->assertSame('BE-WAL', $array['region']);
        $this->assertSame('Namur', $array['names']['fr']);
        $this->assertSame('Namen', $array['names']['nl']);

        $encoded = json_decode((string) json_encode($municipality), true);
        $this->assertIsArray($encoded);
        $this->assertSame('Namur', $encoded['names']['fr']);
    }

    public function test_postal_place_serializes_its_names(): void
    {
        $belgium = Belgium::load();
        $jambes = $belgium->postalPlacesByName('Jambes')[0] ?? null;
        $this->assertNotNull($jambes);

        $this->assertSame(
            [
                'postalCode' => '5100',
                'municipalityNisCode' => '92094',
                'region' => 'BE-WAL',
                'names' => ['fr' => 'Jambes'],
            ],
            $jambes->toArray(),
        );
        $this->assertSame('Jambes', $jambes->name('fr'));
    }

    public function test_postal_code_serializes_its_places(): void
    {
        $belgium = Belgium::load();
        $postalCode = $belgium->postalCode('5100');
        $this->assertNotNull($postalCode);

        $array = $postalCode->toArray();
        $this->assertSame('5100', $array['code']);
        $this->assertNotEmpty($array['places']);
        $names = array_map(static fn(array $place): ?string => $place['names']['fr'] ?? null, $array['places']);
        $this->assertContains('Jambes', $names);
    }

    public function test_region_and_province_serialize_their_names(): void
    {
        $belgium = Belgium::load();

        $region = $belgium->region('BE-BRU');
        $this->assertNotNull($region);
        $regionArray = $region->toArray();
        $this->assertSame('BE-BRU', $regionArray['isoCode']);
        $this->assertSame(['nl' => 'Brussel', 'fr' => 'Bruxelles', 'en' => 'Brussels'], $regionArray['names']);
        $this->assertArrayHasKey('coordinates', $regionArray);
        $this->assertIsFloat($regionArray['coordinates']['latitude']);
        $this->assertIsFloat($regionArray['coordinates']['longitude']);

        $province = $belgium->province('BE-WNA');
        $this->assertNotNull($province);
        $this->assertSame('BE-WAL', $province->toArray()['region']);
        $this->assertSame('Namur', $province->toArray()['names']['fr']);
    }

    public function test_display_name_uses_an_explicit_fallback_chain(): void
    {
        $belgium = Belgium::load();
        $jambes = $belgium->postalPlacesByName('Jambes')[0];
        $this->assertNull($jambes->name('nl'));
        $this->assertSame('Jambes', $jambes->displayName('nl', 'fr'));
        // No implicit first-locale fallback: an absent locale yields null.
        $this->assertNull($jambes->displayName('de'));

        $municipality = $belgium->municipality('92094');
        $this->assertNotNull($municipality);
        $this->assertSame('Namur', $municipality->displayName('xx', 'fr'));
        $this->assertSame('Namen', $municipality->displayName('nl'));
        $this->assertNull($municipality->displayName('en'));
    }
}
