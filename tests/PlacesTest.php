<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use InvalidArgumentException;
use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\PostalPlace;
use LeKoala\BelgianGeography\SupplementaryPlaces;
use PHPUnit\Framework\TestCase;

final class PlacesTest extends TestCase
{
    public function test_brussels_postal_localities_resolve_to_the_city_of_brussels(): void
    {
        $belgium = Belgium::load();

        foreach (['Laeken' => '1020', 'Laken' => '1020', 'Neder-Over-Heembeek' => '1120'] as $name => $code) {
            $places = $belgium->postalPlacesByName($name);
            $this->assertCount(1, $places, $name);
            $this->assertSame($code, $places[0]->postalCode, $name);
            $this->assertSame('21004', $places[0]->municipalityNisCode, $name);
            $this->assertSame('BE-BRU', $places[0]->region, $name);
        }

        $laeken = $belgium->postalPlacesByName('Laeken')[0];
        $this->assertSame('Laeken', $belgium->placeLabel($laeken, 'fr'));
        $this->assertSame('Laken', $belgium->placeLabel($laeken, 'nl'));
    }

    public function test_localities_are_never_promoted_to_municipalities(): void
    {
        $belgium = Belgium::load();

        $this->assertNull($belgium->municipalityByName('Laeken'));
        $this->assertSame([], $belgium->municipalitiesByName('Haren'));
    }

    public function test_haren_is_ambiguous_across_brussels_and_tongeren_borgloon(): void
    {
        $places = array_map(
            static fn(PostalPlace $place): string => $place->postalCode . '/' . $place->municipalityNisCode,
            Belgium::load()->postalPlacesByName('Haren'),
        );
        sort($places);

        $this->assertSame(['1130/21004', '3700/73111', '3840/73111'], $places);
    }

    public function test_supplements_keep_the_best_places_of_their_postal_code(): void
    {
        $belgium = Belgium::load();

        $names = array_map(
            static fn(PostalPlace $place): ?string => $place->name('fr'),
            $belgium->postalCode('1020')?->places() ?? [],
        );
        $this->assertSame(['Bruxelles', 'Laeken'], $names);
        $this->assertSame(['21004'], $belgium->postalCode('1020')?->municipalityNisCodes());
    }

    public function test_a_supplement_already_published_by_best_is_inert(): void
    {
        $best = ['1020' => [['21004', ['nl' => 'Laken', 'fr' => 'Laeken']]]];

        $this->assertSame($best, SupplementaryPlaces::merge($best, $best));
    }

    public function test_a_supplement_cannot_create_a_postal_relation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SupplementaryPlaces::merge(['1000' => [['21004', ['nl' => 'Brussel', 'fr' => 'Bruxelles']]]], [
            '1020' => [['21004', ['nl' => 'Laken', 'fr' => 'Laeken']]],
        ]);
    }
}
