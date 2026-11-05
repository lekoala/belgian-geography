<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Tools\Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The generator must fail loudly on self-inconsistent source data rather than
 * silently keeping the first value: a NIS code never changes region and a locale
 * never carries two different current names.
 */
final class GeneratorTest extends TestCase
{
    public function test_repeated_identical_rows_are_accepted(): void
    {
        $generator = new Generator();
        $generator->ingestAddressCsv(
            $this->stream(
                '11002,,,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
                '11002,,,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
            ),
            'BE-VLG',
        );

        $municipalities = $generator->build()['municipalities'];
        $this->assertSame('Antwerpen', $municipalities[11_002][1]['nl']);
    }

    public function test_a_municipality_in_two_regions_is_rejected(): void
    {
        $generator = new Generator();
        $generator->ingestAddressCsv($this->stream('11002,,,Antwerpen,2000,,ANTWERPEN,BE-VLG,current'), 'BE-VLG');

        $this->expectException(RuntimeException::class);
        $generator->ingestAddressCsv($this->stream('11002,,,Antwerpen,2000,,ANTWERPEN,BE-WAL,current'), 'BE-WAL');
    }

    public function test_conflicting_names_for_the_same_locale_are_rejected(): void
    {
        $generator = new Generator();

        $this->expectException(RuntimeException::class);
        $generator->ingestAddressCsv(
            $this->stream(
                '11002,,,Antwerpen,2000,,ANTWERPEN,BE-VLG,current',
                '11002,,,Antwerp,2000,,ANTWERPEN,BE-VLG,current',
            ),
            'BE-VLG',
        );
    }

    public function test_underscores_around_labels_are_trimmed(): void
    {
        $generator = new Generator();
        $generator->ingestAddressCsv($this->stream('62063,Lüttich,Liège,Luik,4020,Liège_,,BE-WAL,current'), 'BE-WAL');
        $generator->ingestAddressCsv(
            $this->stream('21004,,Bruxelles,Brussel,1000,_BRUXELLES/Laeken_,BRUSSEL_,BE-BRU,current'),
            'BE-BRU',
        );

        $places = $generator->build()['postal_places'];
        $this->assertSame([['62063', ['fr' => 'Liège']]], $places[4020]);
        $names = array_merge(...array_map(static fn(array $place): array => array_values($place[1]), $places[1000]));
        sort($names);
        $this->assertSame(['BRUSSEL', 'BRUXELLES', 'Laeken'], $names);
    }

    public function test_the_most_frequent_spelling_of_a_place_wins(): void
    {
        $rows = [
            '23027,,Hal,Halle,1500,,HALLE,BE-VLG,current',
            '23027,,Hal,Halle,1500,,HALLE,BE-VLG,current',
            '23027,,Hal,Halle,1500,,Halle,BE-VLG,current',
        ];
        foreach ([$rows, array_reverse($rows)] as $order) {
            $generator = new Generator();
            $generator->ingestAddressCsv($this->stream(...$order), 'BE-VLG');

            $this->assertSame([['23027', ['nl' => 'HALLE']]], $generator->build()['postal_places'][1500]);
        }
    }

    public function test_spelling_ties_do_not_depend_on_row_order(): void
    {
        $rows = ['23027,,Hal,Halle,1500,,HALLE,BE-VLG,current', '23027,,Hal,Halle,1500,,Halle,BE-VLG,current'];
        $results = [];
        foreach ([$rows, array_reverse($rows)] as $order) {
            $generator = new Generator();
            $generator->ingestAddressCsv($this->stream(...$order), 'BE-VLG');
            $results[] = $generator->build()['postal_places'][1500];
        }

        $this->assertSame($results[0], $results[1]);
    }

    public function test_address_points_far_from_their_municipality_are_ignored(): void
    {
        $generator = new Generator();
        $generator->ingestAddressCsv(
            $this->pointStream(
                '50.0100,5.5400',
                '50.0200,5.5500',
                '50.0300,5.5300',
                // Inside Belgium's bounding box, but about 130 km away (in France).
                '49.6200,3.7300',
            ),
            'BE-WAL',
        );

        $center = $generator->buildCenters()['municipalities'][82_038];
        $this->assertSame([50.02, 5.54, 3], $center);
        $this->assertSame(['82038' => 1], $generator->outliers());
    }

    /** @return resource */
    private function pointStream(string ...$points)
    {
        $stream = fopen('php://temp', 'wb');
        $this->assertIsResource($stream);
        fwrite(
            $stream,
            'EPSG:4326_lat,EPSG:4326_lon,municipality_id,municipality_name_de,municipality_name_fr,'
            . 'municipality_name_nl,postcode,postname_fr,postname_nl,region_code,status',
        );
        foreach ($points as $point) {
            fwrite($stream, "\n" . $point . ',82038,,Sainte-Ode,,6680,Sainte-Ode,,BE-WAL,current');
        }
        rewind($stream);

        return $stream;
    }

    /** @return resource */
    private function stream(string ...$rows)
    {
        $stream = fopen('php://temp', 'wb');
        $this->assertIsResource($stream);
        fwrite(
            $stream,
            'municipality_id,municipality_name_de,municipality_name_fr,municipality_name_nl,'
            . 'postcode,postname_fr,postname_nl,region_code,status',
        );
        foreach ($rows as $row) {
            fwrite($stream, "\n" . $row);
        }
        rewind($stream);

        return $stream;
    }
}
