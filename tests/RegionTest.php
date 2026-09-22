<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

final class RegionTest extends TestCase
{
    public function test_regions_expose_trilingual_names(): void
    {
        $belgium = Belgium::load();

        $this->assertCount(3, $belgium->regions());

        $brussels = $belgium->region('be-bru');
        $this->assertNotNull($brussels);
        $this->assertSame('Brussel', $brussels->name('nl'));
        $this->assertSame('Bruxelles', $brussels->name('fr'));
        $this->assertSame('Brussels', $brussels->name('en'));

        $this->assertNull($belgium->region('BE-XX'));
    }

    public function test_every_municipality_maps_to_a_region(): void
    {
        $belgium = Belgium::load();

        $missing = [];
        foreach ($belgium->municipalities() as $municipality) {
            if ($belgium->regionForMunicipality($municipality->nisCode) === null) {
                $missing[] = $municipality->nisCode;
            }
        }
        $this->assertSame([], $missing);

        $this->assertSame('BE-VLG', $belgium->regionForMunicipality('11002')?->isoCode);
        $this->assertSame('BE-WAL', $belgium->regionForMunicipality('62063')?->isoCode);
        $this->assertSame('BE-BRU', $belgium->regionForMunicipality('21004')?->isoCode);
        $this->assertNull($belgium->regionForMunicipality('99999'));
        $this->assertNull($belgium->regionForMunicipality('11000'));
    }

    public function test_municipalities_can_be_filtered_by_region(): void
    {
        $belgium = Belgium::load();

        $this->assertCount(19, $belgium->municipalitiesForRegion('BE-BRU'));
        $this->assertSame(
            ['21001', '21002'],
            array_map(
                static fn($municipality): string => $municipality->nisCode,
                array_slice($belgium->municipalitiesForRegion('BE-BRU'), 0, 2),
            ),
        );
        $this->assertSame([], $belgium->municipalitiesForRegion('BE-XX'));
    }
}
