<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\Tools\Exporter;
use LeKoala\BelgianGeography\Tools\Generator;
use PHPUnit\Framework\TestCase;

/**
 * The generated centers companion stores an approximate municipality center as
 * the mean of the current BeST address points, plus the contributing address
 * count. Province and region centers are recomposed at load time as the
 * barycenter of their municipalities' points, weighted by that count.
 */
final class CentersTest extends TestCase
{
    public function test_municipality_center_is_the_mean_of_current_addresses(): void
    {
        $belgium = $this->belgium();

        $antwerpen = $belgium->municipality('11002')?->coordinates;
        $this->assertNotNull($antwerpen);
        $this->assertEqualsWithDelta(51.219_70, $antwerpen->latitude, 0.000_01);
        $this->assertEqualsWithDelta(4.403_75, $antwerpen->longitude, 0.000_01);
    }

    public function test_retired_addresses_do_not_contribute(): void
    {
        $belgium = $this->belgium();

        $namur = $belgium->municipality('92094')?->coordinates;
        $this->assertNotNull($namur);
        // The retired 50.0000 / 4.0000 row must not drag the mean down.
        $this->assertEqualsWithDelta(50.461_95, $namur->latitude, 0.000_01);
        $this->assertEqualsWithDelta(4.870_25, $namur->longitude, 0.000_01);
    }

    public function test_province_center_is_weighted_by_address_count(): void
    {
        $belgium = $this->belgium();

        // BE-VAN holds a single municipality (11002, weight 2).
        $antwerpen = $belgium->province('BE-VAN')?->coordinates;
        $this->assertNotNull($antwerpen);
        $this->assertEqualsWithDelta(51.219_70, $antwerpen->latitude, 0.000_01);
        $this->assertEqualsWithDelta(4.403_75, $antwerpen->longitude, 0.000_01);
    }

    public function test_region_center_is_the_barycenter_of_its_municipalities(): void
    {
        $belgium = $this->belgium();

        // BE-VLG: 11002 (weight 2) + 31005 (weight 1).
        $flanders = $belgium->region('BE-VLG')?->coordinates;
        $this->assertNotNull($flanders);
        $this->assertEqualsWithDelta(51.216_23, $flanders->latitude, 0.000_01);
        $this->assertEqualsWithDelta(4.010_73, $flanders->longitude, 0.000_01);

        // BE-WAL: 62063 (weight 1) + 92094 (weight 2).
        $wallonia = $belgium->region('BE-WAL')?->coordinates;
        $this->assertNotNull($wallonia);
        $this->assertEqualsWithDelta(50.518_83, $wallonia->latitude, 0.000_01);
        $this->assertEqualsWithDelta(5.106_73, $wallonia->longitude, 0.000_01);
    }

    public function test_brussels_has_a_region_center_but_no_province(): void
    {
        $belgium = $this->belgium();

        $this->assertNull($belgium->provinceForMunicipality('21004'));
        $this->assertNotNull($belgium->region('BE-BRU')?->coordinates);
    }

    public function test_out_of_range_coordinates_are_ignored(): void
    {
        $stream = fopen('php://temp', 'wb');
        $this->assertIsResource($stream);
        fwrite($stream, implode("\n", [
            'EPSG:4326_lat,EPSG:4326_lon,municipality_id,municipality_name_de,municipality_name_fr,municipality_name_nl,postcode,postname_fr,postname_nl,region_code,status',
            // Swapped lat/lon and an out-of-Belgium point must both be dropped.
            '4.4025,51.2194,11002,,Anvers,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
            '45.0000,9.0000,11002,,Anvers,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
            '51.2194,4.4025,11002,,Anvers,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
        ]));
        rewind($stream);

        $generator = new Generator();
        $generator->ingestAddressCsv($stream, 'BE-VLG');
        fclose($stream);

        $centers = $generator->buildCenters()['municipalities'];
        $this->assertSame([11_002], array_keys($centers));
        $this->assertSame(1, $centers[11_002][2]);
        $this->assertEqualsWithDelta(51.2194, $centers[11_002][0], 0.000_01);
        $this->assertEqualsWithDelta(4.4025, $centers[11_002][1], 0.000_01);
    }

    public function test_centers_export_round_trips_through_a_php_file(): void
    {
        $generator = new Generator();
        foreach ([
            'BE-VLG' => __DIR__ . '/Fixtures/Flanders_addresses.csv',
            'BE-BRU' => __DIR__ . '/Fixtures/Brussels_addresses.csv',
            'BE-WAL' => __DIR__ . '/Fixtures/Wallonia_addresses.csv',
        ] as $region => $file) {
            $stream = fopen($file, 'rb');
            $this->assertIsResource($stream);
            $generator->ingestAddressCsv($stream, $region);
            fclose($stream);
        }

        $centers = $generator->buildCenters(['BE-VLG' => ['url' => 'https://example.test/vlg.zip', 'sha256' => 'abc']]);
        $exported = Exporter::exportCenters($centers);
        $this->assertStringContainsString("'schema_version' => 1,", $exported);

        $path = tempnam(sys_get_temp_dir(), 'belgian-geography');
        $this->assertIsString($path);
        try {
            file_put_contents($path, $exported);
            $restored = require $path;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->assertSame($centers, $restored);
    }

    private function belgium(): Belgium
    {
        $generator = new Generator();
        foreach ([
            'BE-VLG' => __DIR__ . '/Fixtures/Flanders_addresses.csv',
            'BE-BRU' => __DIR__ . '/Fixtures/Brussels_addresses.csv',
            'BE-WAL' => __DIR__ . '/Fixtures/Wallonia_addresses.csv',
        ] as $region => $file) {
            $stream = fopen($file, 'rb');
            $this->assertIsResource($stream);
            $generator->ingestAddressCsv($stream, $region);
            fclose($stream);
        }

        $data = $generator->build();
        $data['centers'] = $generator->buildCenters()['municipalities'];
        $data['provinces'] = require dirname(__DIR__) . '/resources/provinces.php';
        $data['regions'] = require dirname(__DIR__) . '/resources/regions.php';

        return new Belgium($data);
    }
}
