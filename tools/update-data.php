<?php

declare(strict_types=1);

use LeKoala\BelgianGeography\Tools\Exporter;
use LeKoala\BelgianGeography\Tools\Generator;
use LeKoala\BelgianGeography\Tools\SnapshotWriter;
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

$options = getopt('', ['output::', 'output-centers::', 'source-dir::']);
$output = $options['output'] ?? dirname(__DIR__) . '/resources/data.php';
$centersOutput = $options['output-centers'] ?? null;
if ($centersOutput === null) {
    $centersOutput = dirname($output) . '/centers.php';
}
$sourceDir = $options['source-dir'] ?? null;
// Only downloads need a scratch directory; with --source-dir the archives are
// read in place.
$tempDir = $sourceDir === null ? sys_get_temp_dir() . '/belgian-geography-' . bin2hex(random_bytes(5)) : null;
if ($tempDir !== null && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
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

    // Build both snapshots before touching disk, then replace them as one unit.
    $data = $generator->build($metadata);
    $centers = $generator->buildCenters($metadata);
    SnapshotWriter::write([
        $output => Exporter::export($data),
        $centersOutput => Exporter::exportCenters($centers),
    ]);

    fwrite(STDOUT, sprintf("Generated at %s (files stay deterministic).\n", gmdate(DATE_ATOM)));
    fwrite(STDOUT, sprintf(
        "Wrote %s (%d municipalities, %d postal codes).\n",
        $output,
        count($data['municipalities']),
        count($data['postal_places']),
    ));
    fwrite(STDOUT, sprintf(
        "Wrote %s (%d municipality centers).\n",
        $centersOutput,
        count($centers['municipalities']),
    ));
} finally {
    if ($tempDir !== null && is_dir($tempDir)) {
        foreach (glob($tempDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($tempDir);
    }
}
