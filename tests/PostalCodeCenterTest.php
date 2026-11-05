<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

final class PostalCodeCenterTest extends TestCase
{
    public function test_a_single_municipality_code_uses_its_center(): void
    {
        $belgium = Belgium::load();

        $this->assertSame($belgium->municipality('38014')?->coordinates, $belgium->centerForPostalCode('8670'));
    }

    public function test_a_shared_code_prefers_the_named_municipality(): void
    {
        $belgium = Belgium::load();
        $ixelles = $belgium->municipality('21009')?->coordinates;
        $brussels = $belgium->municipality('21004')?->coordinates;
        $this->assertNotNull($ixelles);
        $this->assertNotSame($ixelles, $brussels);

        foreach (['Ixelles', 'Elsene', 'ixelles'] as $name) {
            $this->assertSame($ixelles, $belgium->centerForPostalCode('1050', $name), $name);
        }
        $this->assertSame($brussels, $belgium->centerForPostalCode(1050, 'Bruxelles'));
        // Names are tried in order; an unknown name is skipped.
        $this->assertSame($ixelles, $belgium->centerForPostalCode('1050', 'Nowhere', 'Elsene', 'Brussel'));
    }

    public function test_without_a_match_the_first_municipality_is_used(): void
    {
        $belgium = Belgium::load();
        $first = $belgium->municipalitiesForPostalCode('1050')[0]->coordinates;

        $this->assertSame($first, $belgium->centerForPostalCode('1050'));
        $this->assertSame($first, $belgium->centerForPostalCode('1050', 'Namur'));
    }

    public function test_unknown_codes_have_no_center(): void
    {
        $belgium = Belgium::load();

        $this->assertNull($belgium->centerForPostalCode('0000'));
        $this->assertNull($belgium->centerForPostalCode('abc'));
    }
}
