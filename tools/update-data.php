<?php

declare(strict_types=1);

use LeKoala\BelgianGeography\Tools\Exporter;
use LeKoala\BelgianGeography\Tools\Generator;
require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "ext-zip is required to refresh BeST data.\n");
    exit(1);
}

$sources = [
    'BE-VLG' => 'https://opendata.bosa.be/download/best/openaddress-bevlg.zip',
    'BE-BRU' => 'https://opendata.bosa.be/download/best/openaddress-bebru.zip',
    'BE-WAL' => 'https://opendata.bosa.be/download/best/openaddress-bewal.zip',
];

$options = getopt('', ['output::', 'source-dir::']);
$output = $options['output'] ?? dirname(__DIR__) . '/resources/data.php';
$sourceDir = $options['source-dir'] ?? null;
$tempDir = sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(5));
if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    throw new RuntimeException('Could not create temporary directory.');
}

$generator = new Generator();
$metadata = [];

try {
    foreach ($sources as $region => $url) {
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: $region . '.zip');
        $zipPath = $sourceDir !== null ? rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR . $filename : $tempDir . DIRECTORY_SEPARATOR . $filename;

        if ($sourceDir === null) {
            fwrite(STDOUT, sprintf("Downloading %s...\n", $url));
            $context = stream_context_create(['http' => [
                'timeout' => 120,
                'user_agent' => 'lekoala/belgian-geography data updater',
                'follow_location' => 1,
            ]]);
            $in = fopen($url, 'rb', false, $context);
            if ($in === false) {
                throw new RuntimeException(sprintf('Could not download %s.', $url));
            }
            $out = fopen($zipPath, 'wb');
            if ($out === false) {
                throw new RuntimeException(sprintf('Could not write %s.', $zipPath));
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        }

        if (!is_file($zipPath)) {
            throw new RuntimeException(sprintf('Missing source archive %s.', $zipPath));
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException(sprintf('Could not open %s.', $zipPath));
        }

        $addressEntry = null;
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('~(?:^|/)openaddress-be(?:vlg|bru|wal)\.csv$~i', $name) === 1) {
                $addressEntry = $name;
                break;
            }
        }
        if ($addressEntry === null) {
            $zip->close();
            throw new RuntimeException(sprintf('No openaddress-be*.csv found in %s.', $zipPath));
        }

        fwrite(STDOUT, sprintf("Reading %s (%s)...\n", $addressEntry, $region));
        $stream = $zip->getStream($addressEntry);
        if ($stream === false) {
            $zip->close();
            throw new RuntimeException(sprintf('Could not read %s from %s.', $addressEntry, $zipPath));
        }
        $generator->ingestAddressCsv($stream, $region);
        fclose($stream);
        $zip->close();

        $metadata[$region] = [
            'url' => $url,
            'sha256' => hash_file('sha256', $zipPath) ?: '',
        ];
    }

    $data = $generator->build($metadata);
    $contents = Exporter::export($data);
    if (file_put_contents($output, $contents) === false) {
        throw new RuntimeException(sprintf('Could not write %s.', $output));
    }

    fwrite(STDOUT, sprintf(
        "Wrote %s (%d municipalities, %d postal codes).\n",
        $output,
        count($data['municipalities']),
        count($data['postal_places']),
    ));
} finally {
    if ($sourceDir === null && is_dir($tempDir)) {
        foreach (glob($tempDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($tempDir);
    }
}
