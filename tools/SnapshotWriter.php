<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tools;

use RuntimeException;
use Throwable;

/**
 * Writes the generated snapshot files as one unit.
 *
 * If any write fails, the files already replaced are restored from their
 * pre-write contents (or removed when they did not exist), so resources/data.php
 * and resources/centers.php never diverge.
 */
final class SnapshotWriter
{
    /**
     * @param array<string, string> $files path => contents
     */
    public static function write(array $files): void
    {
        $backups = [];
        foreach (array_keys($files) as $path) {
            if (!is_file($path)) {
                $backups[$path] = null;
                continue;
            }
            $contents = @file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException(sprintf('Could not read %s before replacing it.', $path));
            }
            $backups[$path] = $contents;
        }

        $written = [];
        try {
            foreach ($files as $path => $contents) {
                if (@file_put_contents($path, $contents) === false) {
                    throw new RuntimeException(sprintf('Could not write %s.', $path));
                }
                $written[] = $path;
            }
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                $backup = $backups[$path] ?? null;
                if ($backup === null) {
                    @unlink($path);
                } else {
                    @file_put_contents($path, $backup);
                }
            }
            throw $exception;
        }
    }
}
