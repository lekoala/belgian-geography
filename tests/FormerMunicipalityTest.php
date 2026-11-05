<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\LocalizedName;
use PHPUnit\Framework\TestCase;

final class FormerMunicipalityTest extends TestCase
{
    public function test_every_former_name_resolves_to_the_municipality_that_absorbed_it(): void
    {
        $belgium = Belgium::load();
        $former = require dirname(__DIR__) . '/resources/former.php';

        foreach ($former as $nisCode => $entries) {
            $this->assertNotNull($belgium->municipality((string) $nisCode), (string) $nisCode);
            foreach ($entries as $names) {
                foreach ($names as $name) {
                    $this->assertSame((string) $nisCode, $belgium->municipalityByName($name)?->nisCode, $name);
                }
            }
        }
    }

    public function test_merged_municipalities_resolve_in_every_language(): void
    {
        $belgium = Belgium::load();

        foreach (['Tongeren', 'Tongres', 'Tongern', 'Borgloon', 'Looz'] as $name) {
            $this->assertSame('73111', $belgium->municipalityByName($name)?->nisCode, $name);
        }
        $this->assertSame('23106', $belgium->municipalityByName('Gammerages')?->nisCode);
        $this->assertSame('11002', $belgium->municipalityByName('Borsbeek')?->nisCode);
    }

    public function test_former_names_are_not_current_names(): void
    {
        $belgium = Belgium::load();
        $tongerenBorgloon = $belgium->municipality('73111');
        $this->assertNotNull($tongerenBorgloon);

        $this->assertSame('Tongeren-Borgloon', $tongerenBorgloon->name('nl'));
        $this->assertSame(
            [['nl' => 'Tongeren', 'fr' => 'Tongres', 'de' => 'Tongern'], ['nl' => 'Borgloon', 'fr' => 'Looz']],
            array_map(static fn(LocalizedName $names): array => $names->all(), $tongerenBorgloon->formerNames),
        );
        $this->assertSame([], $belgium->municipality('21004')?->formerNames);
    }

    public function test_a_former_name_never_shadows_a_current_name(): void
    {
        $belgium = new Belgium([
            'municipalities' => [
                '11001' => ['BE-VLG', ['nl' => 'Alpha']],
                '11002' => ['BE-VLG', ['nl' => 'Beta']],
            ],
            'former' => ['11002' => [['nl' => 'Alpha']]],
        ]);

        $this->assertSame('11001', $belgium->municipalityByName('Alpha')?->nisCode);
    }

    public function test_french_exonyms_of_flemish_municipalities_resolve(): void
    {
        $belgium = Belgium::load();

        foreach ([
            'Coxyde' => 'Koksijde',
            'Wervicq' => 'Wervik',
            'Thourout' => 'Torhout',
            'Le Coq' => 'De Haan',
            'Laethem-Saint-Martin' => 'Sint-Martens-Latem',
            'Wavre-Sainte-Catherine' => 'Sint-Katelijne-Waver',
        ] as $exonym => $name) {
            $this->assertSame($name, $belgium->municipalityByName($exonym)?->name('nl'), $exonym);
        }
    }
}
