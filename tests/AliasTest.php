<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\Normalizer;
use PHPUnit\Framework\TestCase;

final class AliasTest extends TestCase
{
    public function test_every_search_alias_resolves_to_its_documented_municipality(): void
    {
        $belgium = Belgium::load();
        $aliases = require dirname(__DIR__) . '/resources/aliases.php';

        // The number of aliases is deliberately not asserted: the layer is a
        // search convenience, not a contract.
        foreach ($aliases as $alias => $nisCode) {
            $this->assertSame($alias, Normalizer::key($alias), "alias '{$alias}' must be pre-normalized");
            $municipality = $belgium->municipalityByName($alias);
            $this->assertNotNull($municipality, "alias '{$alias}' resolves");
            $this->assertSame($nisCode, $municipality->nisCode, "alias '{$alias}' targets {$nisCode}");
        }
    }

    public function test_brussels_variants_resolve_to_the_city_of_brussels(): void
    {
        $belgium = Belgium::load();

        foreach (['Bruxelles', 'Brussel', 'Brussels', 'Brüssel'] as $name) {
            $this->assertSame('21004', $belgium->municipalityByName($name)?->nisCode, $name);
        }
    }

    public function test_english_search_aliases_resolve_to_the_native_municipality(): void
    {
        $belgium = Belgium::load();

        $this->assertSame('11002', $belgium->municipalityByName('Antwerp')?->nisCode);
        $this->assertSame('11002', $belgium->municipalityByName('Antwerpen')?->nisCode);
        $this->assertSame('44021', $belgium->municipalityByName('Ghent')?->nisCode);
        $this->assertSame('44021', $belgium->municipalityByName('Gent')?->nisCode);
    }

    public function test_short_forms_of_brussels_municipalities_resolve(): void
    {
        $belgium = Belgium::load();

        $this->assertSame('21012', $belgium->municipalityByName('Molenbeek')?->nisCode);
        $this->assertSame('21014', $belgium->municipalityByName('Saint-Josse')?->nisCode);
        $this->assertSame('21014', $belgium->municipalityByName('Sint-Joost')?->nisCode);
        $this->assertSame('21010', $belgium->municipalityByName('Jette')?->nisCode);
    }

    public function test_merged_tongeren_borgloon_keeps_its_french_search_alias(): void
    {
        $belgium = Belgium::load();

        $this->assertSame('73111', $belgium->municipalityByName('Tongres')?->nisCode);
        $this->assertSame('73111', $belgium->municipalityByName('Tongeren-Borgloon')?->nisCode);
    }
}
