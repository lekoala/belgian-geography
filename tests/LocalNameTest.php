<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\Municipality;
use PHPUnit\Framework\TestCase;

final class LocalNameTest extends TestCase
{
    public function test_local_name_follows_the_language_area(): void
    {
        $belgium = Belgium::load();

        $this->assertSame('Koksijde', $belgium->municipality('38014')?->localName());
        $this->assertSame('nl', $belgium->municipality('38014')?->localLocale());
        $this->assertSame('Flobecq', $belgium->municipality('51019')?->localName());
        $this->assertSame('fr', $belgium->municipality('51019')?->localLocale());
        $this->assertSame('Kelmis', $belgium->municipality('63040')?->localName());
        $this->assertSame('de', $belgium->municipality('63040')?->localLocale());
    }

    public function test_brussels_has_no_single_local_name(): void
    {
        $ixelles = Belgium::load()->municipality('21009');

        $this->assertNull($ixelles?->localLocale());
        $this->assertNull($ixelles?->localName());
    }

    public function test_every_german_speaking_municipality_is_walloon_and_has_a_german_name(): void
    {
        $belgium = Belgium::load();

        foreach (Municipality::GERMAN_SPEAKING as $nisCode) {
            $municipality = $belgium->municipality($nisCode);
            $this->assertNotNull($municipality, $nisCode);
            $this->assertSame('BE-WAL', $municipality->region, $nisCode);
            $this->assertNotNull($municipality->localName(), $nisCode);
        }
    }

    public function test_every_municipality_outside_brussels_has_a_local_name(): void
    {
        foreach (Belgium::load()->municipalities() as $municipality) {
            if ($municipality->region !== 'BE-BRU') {
                $this->assertNotNull($municipality->localName(), $municipality->nisCode);
            }
        }
    }
}
