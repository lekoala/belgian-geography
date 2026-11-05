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
