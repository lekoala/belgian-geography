<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

final class ProvinceTest extends TestCase
{
    public function test_provinces_expose_trilingual_names(): void
    {
        $belgium = Belgium::load();

        $this->assertCount(10, $belgium->provinces());

        $westFlanders = $belgium->province('be-vwv');
        $this->assertNotNull($westFlanders);
        $this->assertSame('West-Vlaanderen', $westFlanders->name('nl'));
        $this->assertSame('Flandre occidentale', $westFlanders->name('fr'));
        $this->assertSame('West Flanders', $westFlanders->name('en'));
        $this->assertSame('BE-VLG', $westFlanders->region);

        // The Brussels-Capital Region is a region, not a province.
        $this->assertNull($belgium->province('BE-BRU'));
        $this->assertNull($belgium->province('BE-XX'));
    }

    public function test_every_non_brussels_municipality_maps_to_a_province(): void
    {
        $belgium = Belgium::load();

        $missing = [];
        foreach ($belgium->municipalities() as $municipality) {
            if (
                $municipality->region !== 'BE-BRU'
                && $belgium->provinceForMunicipality($municipality->nisCode) === null
            ) {
                $missing[] = $municipality->nisCode;
            }
        }
        $this->assertSame([], $missing);

        $this->assertSame('BE-VAN', $belgium->provinceForMunicipality('11002')?->isoCode);
        $this->assertNull($belgium->provinceForMunicipality('21004'));
        $this->assertSame('BE-WLG', $belgium->provinceForMunicipality('62063')?->isoCode);
        $this->assertSame('BE-WNA', $belgium->provinceForMunicipality('92094')?->isoCode);
        $this->assertSame('BE-VLI', $belgium->provinceForMunicipality('73111')?->isoCode);
        $this->assertNull($belgium->provinceForMunicipality('99999'));
        $this->assertNull($belgium->provinceForMunicipality('11000'));
    }

    public function test_postal_codes_can_be_listed_by_region_and_province(): void
    {
        $belgium = Belgium::load();

        $brusselsCodes = array_map(
            static fn($postalCode): string => $postalCode->code,
            $belgium->postalCodesForRegion('BE-BRU'),
        );
        $this->assertContains('1000', $brusselsCodes);
        $this->assertContains('1040', $brusselsCodes);
        $this->assertNotContains('2000', $brusselsCodes);

        $westFlandersCodes = array_map(
            static fn($postalCode): string => $postalCode->code,
            $belgium->postalCodesForProvince('BE-VWV'),
        );
        $this->assertContains('8000', $westFlandersCodes);
        $this->assertNotContains('1000', $westFlandersCodes);

        $this->assertSame([], $belgium->postalCodesForRegion('BE-XX'));
        $this->assertSame([], $belgium->postalCodesForProvince('BE-XX'));
        $this->assertSame([], $belgium->postalCodesForProvince('BE-BRU'));
        $this->assertCount(19, $belgium->municipalitiesForRegion('BE-BRU'));
        $this->assertSame([], $belgium->municipalitiesForProvince('BE-BRU'));
        $this->assertSame([], $belgium->municipalitiesForProvince('BE-XX'));
        $this->assertSame([], $belgium->municipalitiesForRegion('BE-XX'));
    }

    public function test_postal_code_lookup_can_be_restricted_to_a_region(): void
    {
        $belgium = Belgium::load();

        $this->assertCount(2, $belgium->municipalitiesForPostalCode('1040', 'BE-BRU'));
        $this->assertSame([], $belgium->municipalitiesForPostalCode('1040', 'BE-VLG'));
        $this->assertSame([], $belgium->municipalitiesForPostalCode('9999', 'BE-BRU'));
    }
}
