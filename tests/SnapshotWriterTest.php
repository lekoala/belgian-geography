<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Tools\SnapshotWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SnapshotWriterTest extends TestCase
{
    private string $directory = '';

    protected function tearDown(): void
    {
        if ($this->directory === '' || !is_dir($this->directory)) {
            return;
        }
        $files = glob($this->directory . '/*');
        foreach ($files === false ? [] : $files as $file) {
            if (is_dir($file)) {
                rmdir($file);
                continue;
            }
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function test_it_writes_every_file(): void
    {
        $directory = $this->makeDirectory();
        $first = $directory . '/data.php';
        $second = $directory . '/centers.php';

        SnapshotWriter::write([$first => 'data', $second => 'centers']);

        $this->assertSame('data', file_get_contents($first));
        $this->assertSame('centers', file_get_contents($second));
    }

    public function test_it_restores_an_existing_file_when_a_later_write_fails(): void
    {
        $directory = $this->makeDirectory();
        $first = $directory . '/data.php';
        file_put_contents($first, 'original');
        $unwritable = $directory . '/missing/centers.php';

        $thrown = null;
        try {
            SnapshotWriter::write([$first => 'replacement', $unwritable => 'centers']);
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown);
        $this->assertSame('original', file_get_contents($first));
        $this->assertFileDoesNotExist($unwritable);
    }

    public function test_it_removes_a_created_file_when_a_later_write_fails(): void
    {
        $directory = $this->makeDirectory();
        $first = $directory . '/data.php';
        $unwritable = $directory . '/missing/centers.php';

        $thrown = null;
        try {
            SnapshotWriter::write([$first => 'data', $unwritable => 'centers']);
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown);
        $this->assertFileDoesNotExist($first);
    }

    private function makeDirectory(): string
    {
        $this->directory = sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($this->directory));

        return $this->directory;
    }
}
