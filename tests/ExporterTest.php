<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Tools\Exporter;
use LeKoala\BelgianGeography\Tools\Generator;
use PHPUnit\Framework\TestCase;

final class ExporterTest extends TestCase
{
    public function test_export_round_trips_through_a_php_file(): void
    {
        $generator = new Generator();
        foreach ([
            __DIR__ . '/Fixtures/Flanders_addresses.csv',
            __DIR__ . '/Fixtures/Brussels_addresses.csv',
            __DIR__ . '/Fixtures/Wallonia_addresses.csv',
        ] as $file) {
            $stream = fopen($file, 'rb');
            $this->assertIsResource($stream);
            $region = 'BE-WAL';
            if (str_contains($file, 'Flanders')) {
                $region = 'BE-VLG';
            } elseif (str_contains($file, 'Brussels')) {
                $region = 'BE-BRU';
            }
            $generator->ingestAddressCsv($stream, $region);
            fclose($stream);
        }
        $data = $generator->build(['BE-VLG' => ['url' => 'https://example.test/vlg.zip', 'sha256' => 'abc']]);

        $exported = Exporter::export($data);
        $this->assertStringStartsWith("<?php\n\ndeclare(strict_types=1);\n\nreturn [", $exported);
        $this->assertStringNotContainsString('array (', $exported);
        $this->assertStringContainsString("'schema_version' => 1,", $exported);
        $this->assertStringContainsString("  5100 => [['92094', ['fr' => 'JAMBES']]],", $exported);

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

        $this->assertEquals($data, $restored);
    }
}
