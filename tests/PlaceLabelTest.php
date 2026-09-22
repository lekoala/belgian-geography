<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use PHPUnit\Framework\TestCase;

final class PlaceLabelTest extends TestCase
{
    public function test_display_label_reuses_the_municipality_spelling(): void
    {
        $belgium = Belgium::load();
        $place = $belgium->postalCode('1500')?->places()[0] ?? null;
        $this->assertNotNull($place);

        // Source spelling is preserved...
        $this->assertSame('HALLE', $place->name('nl'));
        // ...but the display label reuses the municipality graphy.
        $this->assertSame('Halle', $belgium->placeLabel($place, 'nl'));
    }

    public function test_display_label_keeps_the_source_when_the_names_differ(): void
    {
        $belgium = Belgium::load();

        // 1540 "HERNE" is a former municipality, now a locality of Pajottegem.
        $herne = $belgium->postalPlacesByName('Herne')[0] ?? null;
        $this->assertNotNull($herne);
        $this->assertSame('HERNE', $herne->name('nl'));
        $this->assertSame('HERNE', $belgium->placeLabel($herne, 'nl'));

        // 2980 "Halle" is a locality of Zoersel; its graphy is already mixed case.
        $zoersel = $belgium->postalCode('2980')?->places()[0] ?? null;
        $this->assertNotNull($zoersel);
        $this->assertSame('Halle', $belgium->placeLabel($zoersel, 'nl'));
    }

    public function test_display_label_falls_back_across_locales(): void
    {
        $belgium = Belgium::load();
        $place = $belgium->postalCode('1500')?->places()[0] ?? null;
        $this->assertNotNull($place);

        // No French name: fall back to Dutch, and still reuse the municipality graphy.
        $this->assertNull($place->name('fr'));
        $this->assertSame('Halle', $belgium->placeLabel($place, 'fr', 'nl'));
    }

    public function test_display_label_returns_null_without_a_matching_locale(): void
    {
        $belgium = Belgium::load();
        $place = $belgium->postalCode('1500')?->places()[0] ?? null;
        $this->assertNotNull($place);

        // 1500 "HALLE" has no French name: no implicit first-locale fallback.
        $this->assertNull($place->name('fr'));
        $this->assertNull($belgium->placeLabel($place, 'fr'));
    }

    public function test_locality_search_is_case_insensitive(): void
    {
        $belgium = Belgium::load();

        $this->assertCount(2, $belgium->postalPlacesByName('Halle'));
        $this->assertCount(2, $belgium->postalPlacesByName('HALLE'));
        $this->assertCount(2, $belgium->postalPlacesByName('halle'));
    }
}
