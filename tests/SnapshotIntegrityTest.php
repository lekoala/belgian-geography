<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

/**
 * Guards the generated snapshot against incomplete future updates: every
 * locality must reference a real municipality and expose at least one name;
 * every municipality must expose a name and a known region.
 */
final class SnapshotIntegrityTest extends TestCase
{
    public function test_every_municipality_has_a_name_and_a_known_region(): void
    {
        $belgium = Belgium::load();

        $problems = [];
        foreach ($belgium->municipalities() as $municipality) {
            if ($municipality->names->all() === []) {
                $problems[] = sprintf('%s: no name', $municipality->nisCode);
            }
            if ($belgium->regionForMunicipality($municipality->nisCode) === null) {
                $problems[] = sprintf('%s: unknown region "%s"', $municipality->nisCode, $municipality->region);
            }
        }

        $this->assertSame([], $problems);
    }

    public function test_the_packaged_snapshot_has_a_center_for_every_municipality(): void
    {
        $belgium = Belgium::load();

        $missing = [];
        foreach ($belgium->municipalities() as $municipality) {
            if ($municipality->coordinates !== null) {
                continue;
            }

            $missing[] = $municipality->nisCode;
        }

        $this->assertSame([], $missing);
    }

    public function test_packaged_coordinates_stay_within_belgium_bounds(): void
    {
        $belgium = Belgium::load();

        $points = [];
        foreach ($belgium->municipalities() as $municipality) {
            $points[$municipality->nisCode] = $municipality->coordinates;
        }
        foreach ($belgium->provinces() as $province) {
            $points[$province->isoCode] = $province->coordinates;
        }
        foreach ($belgium->regions() as $region) {
            $points[$region->isoCode] = $region->coordinates;
        }

        $outOfBounds = [];
        foreach ($points as $key => $coordinates) {
            if ($coordinates === null) {
                continue;
            }
            if (
                $coordinates->latitude <= 49.0
                || $coordinates->latitude >= 52.0
                || $coordinates->longitude <= 2.0
                || $coordinates->longitude >= 7.0
            ) {
                $outOfBounds[] = $key;
            }
        }

        $this->assertSame([], $outOfBounds);
    }

    public function test_every_postal_place_is_consistent_with_its_municipality(): void
    {
        $belgium = Belgium::load();

        $problems = [];
        foreach ($belgium->postalCodes() as $postalCode) {
            foreach ($postalCode->places() as $place) {
                if ($place->postalCode !== $postalCode->code) {
                    $problems[] = sprintf('%s: place carries postal code %s', $postalCode->code, $place->postalCode);
                }
                if ($place->names->all() === []) {
                    $problems[] = sprintf(
                        '%s: locality for %s has no name',
                        $place->postalCode,
                        $place->municipalityNisCode,
                    );
                }
                $municipality = $belgium->municipality($place->municipalityNisCode);
                if ($municipality === null) {
                    $problems[] = sprintf(
                        '%s: unknown municipality %s',
                        $place->postalCode,
                        $place->municipalityNisCode,
                    );
                }
            }
        }

        $this->assertSame([], $problems);
    }
}
