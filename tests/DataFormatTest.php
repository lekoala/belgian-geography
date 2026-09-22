<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\DataLoader;
use LeKoala\BelgianGeography\Exception\DataNotGenerated;
use PHPUnit\Framework\TestCase;

/**
 * The snapshot is a versioned tuple format: a municipality is
 * [region, names] and a locality is [municipality NIS code, names]. A locality
 * inherits its region from the referenced municipality. These tests pin the
 * schema contract.
 */
final class DataFormatTest extends TestCase
{
    private string $directory = '';

    protected function tearDown(): void
    {
        if ($this->directory !== '' && is_dir($this->directory)) {
            $files = glob($this->directory . '/*');
            foreach ($files === false ? [] : $files as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
    }

    public function test_a_locality_inherits_the_region_of_its_municipality(): void
    {
        $file = $this->writeSnapshot([
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => [
                '92094' => ['BE-WAL', ['fr' => 'Namur']],
            ],
            'postal_places' => [
                '5100' => [
                    ['92094', ['fr' => 'Jambes']],
                ],
            ],
        ]);

        $belgium = Belgium::load($file, false);

        $place = $belgium->postalPlacesByName('Jambes')[0] ?? null;
        $this->assertNotNull($place);
        $this->assertSame('BE-WAL', $place->region);
        $this->assertSame('BE-WAL', $belgium->municipality('92094')?->region);
    }

    public function test_a_snapshot_without_centers_has_null_coordinates(): void
    {
        $file = $this->writeSnapshot([
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => ['92094' => ['BE-WAL', ['fr' => 'Namur']]],
            'postal_places' => [],
        ]);

        $this->assertNull(Belgium::load($file, false)->municipality('92094')?->coordinates);
    }

    public function test_an_unsupported_schema_version_is_rejected(): void
    {
        $file = $this->writeSnapshot([
            'meta' => [],
            'municipalities' => [
                '92094' => ['BE-WAL', ['fr' => 'Namur']],
            ],
            'postal_places' => [],
        ]);

        $this->expectException(DataNotGenerated::class);
        Belgium::load($file, false);
    }

    public function test_a_locality_referencing_an_unknown_municipality_is_rejected(): void
    {
        $file = $this->writeSnapshot([
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => [
                '92094' => ['BE-WAL', ['fr' => 'Namur']],
            ],
            'postal_places' => [
                '5100' => [
                    ['99999', ['fr' => 'Ailleurs']],
                ],
            ],
        ]);

        $this->expectException(DataNotGenerated::class);
        Belgium::load($file, false);
    }

    public function test_a_non_tuple_record_is_rejected(): void
    {
        $file = $this->writeSnapshot([
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => [
                '92094' => ['region' => 'BE-WAL', 'names' => ['fr' => 'Namur']],
            ],
            'postal_places' => [],
        ]);

        $this->expectException(DataNotGenerated::class);
        Belgium::load($file, false);
    }

    /** @param array<string, mixed> $data */
    private function writeSnapshot(array $data): string
    {
        $this->directory = sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($this->directory));
        $file = $this->directory . '/data.php';
        file_put_contents($file, '<?php return ' . var_export($data, true) . ';');

        return $file;
    }
}
