<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use InvalidArgumentException;
use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\DataLoader;
use LeKoala\BelgianGeography\Exception\DataNotGenerated;
use LeKoala\BelgianGeography\ReferenceData;
use PHPUnit\Framework\TestCase;

final class CompanionDataTest extends TestCase
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

    public function test_partial_snapshot_can_skip_packaged_references(): void
    {
        $file = $this->writePartialSnapshot();

        $belgium = Belgium::load($file, false);

        $this->assertSame('Antwerpen', $belgium->municipality('11002')?->name('nl'));
        $this->assertSame([], $belgium->provinces());
        $this->assertSame([], $belgium->regions());
        $this->assertSame([], $belgium->postalPlacesByName('Aerschot'));
    }

    public function test_partial_snapshot_rejects_packaged_references_by_default(): void
    {
        $file = $this->writePartialSnapshot();

        $this->expectException(InvalidArgumentException::class);
        Belgium::load($file);
    }

    public function test_a_centers_companion_is_loaded(): void
    {
        $file = $this->writePartialSnapshot();
        $this->writeCenters(['11002' => [51.2194, 4.4025, 7]]);

        $coordinates = Belgium::load($file, false)->municipality('11002')?->coordinates;
        $this->assertNotNull($coordinates);
        $this->assertEqualsWithDelta(51.2194, $coordinates->latitude, 0.000_01);
        $this->assertEqualsWithDelta(4.4025, $coordinates->longitude, 0.000_01);
    }

    public function test_centers_never_fall_back_to_the_packaged_snapshot(): void
    {
        // A snapshot copied elsewhere without its sibling centers.php must not
        // pick up the packaged centers: computed data belongs to its own snapshot.
        $file = $this->writeFullSnapshotCopy();

        $belgium = Belgium::load($file);
        $this->assertSame('Namur', $belgium->municipality('92094')?->name('fr'));
        $this->assertNull($belgium->municipality('92094')?->coordinates);
        $this->assertNull($belgium->region('BE-WAL')?->coordinates);
    }

    public function test_centers_for_unknown_municipalities_are_ignored(): void
    {
        $file = $this->writePartialSnapshot();
        $this->writeCenters(['99999' => [50.0, 4.0, 5]]);

        $this->assertNull(Belgium::load($file, false)->municipality('11002')?->coordinates);
    }

    public function test_an_invalid_centers_companion_is_rejected(): void
    {
        $file = $this->writePartialSnapshot();
        file_put_contents(
            dirname($file) . '/centers.php',
            "<?php return ['schema_version' => 99, 'municipalities' => []];",
        );

        $this->expectException(DataNotGenerated::class);
        Belgium::load($file, false);
    }

    /** @param array<string, array{0:float,1:float,2:int}> $municipalities */
    private function writeCenters(array $municipalities): void
    {
        $data = [
            'schema_version' => ReferenceData::CENTERS_SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => $municipalities,
        ];
        self::assertTrue(
            file_put_contents($this->directory . '/centers.php', '<?php return ' . var_export($data, true) . ';')
            !== false,
        );
    }

    private function writeFullSnapshotCopy(): string
    {
        $this->directory = sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($this->directory));
        $file = $this->directory . '/data.php';
        self::assertTrue(copy(dirname(__DIR__) . '/resources/data.php', $file));

        return $file;
    }

    private function writePartialSnapshot(): string
    {
        $this->directory = sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($this->directory));
        $file = $this->directory . '/data.php';
        $data = [
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [],
            'municipalities' => [
                '11002' => ['BE-VLG', ['nl' => 'Antwerpen']],
            ],
            'postal_places' => [
                '2000' => [
                    ['11002', ['nl' => 'Antwerpen']],
                ],
            ],
        ];
        file_put_contents($file, '<?php return ' . var_export($data, true) . ';');

        return $file;
    }
}
