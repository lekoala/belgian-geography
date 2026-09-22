<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use InvalidArgumentException;
use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\DataLoader;
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
