<?php

declare(strict_types=1);

/*
 * One-shot analysis aid (dev only, export-ignore): confronts common search
 * aliases collected from Wikipedia (EN list of cities, language-facility
 * municipalities, Wallonia list) with the generated BeST snapshot.
 *
 * Usage: php tools/check-alias-coverage.php [--emit]
 *   default : prints a report per candidate (resolved-correct / resolved-wrong /
 *             ambiguous / unresolved / canonical-missing).
 *   --emit  : prints PHP rows "alias-key => NIS" for the unresolved candidates,
 *             ready for manual review into resources/aliases.php.
 *
 * Rule (README): keep a search alias only when it is a plausible search spelling
 * AND missing from the BeST index. Never emit a row that would shadow a current
 * BeST name — Belgium::municipalityByName() already resolves those.
 */

use LeKoala\BelgianGeography\Belgium;
use LeKoala\BelgianGeography\Normalizer;

require dirname(__DIR__) . '/vendor/autoload.php';

// alias input => canonical BeST name used to derive the expected NIS.
// Canonical = native name: nl in Flanders, fr in Wallonia, fr/nl in Brussels.
$candidates = [
    // English usage differing from the native name.
    'Brussels' => 'Bruxelles',
    'Antwerp' => 'Antwerpen',
    'Ghent' => 'Gent',
    'Ypres' => 'Ieper',
    // French search aliases of Flemish municipalities (EN cities page).
    'Alost' => 'Aalst',
    'Aerschot' => 'Aarschot',
    'Anvers' => 'Antwerpen',
    'Aat' => 'Ath',
    'Hal' => 'Halle',
    'Gand' => 'Gent',
    'Bruges' => 'Brugge',
    'Courtrai' => 'Kortrijk',
    'Grammont' => 'Geraardsbergen',
    'Termonde' => 'Dendermonde',
    'Dixmude' => 'Diksmuide',
    'Louvain' => 'Leuven',
    'Malines' => 'Mechelen',
    'Ostende' => 'Oostende',
    'Renaix' => 'Ronse',
    'Tongres' => 'Tongeren',
    // Dutch search aliases of Walloon municipalities (EN cities + Wallonia pages).
    'Aarlen' => 'Arlon',
    'Bastenaken' => 'Bastogne',
    'Edingen' => 'Enghien',
    "Genepiën" => 'Genappe',
    'Geldenaken' => 'Jodoigne',
    'Hannuit' => 'Hannut',
    'Herck-la-Ville' => 'Herk-de-Stad',
    'Hoei' => 'Huy',
    'Komen-Waasten' => 'Comines-Warneton',
    'Lessen' => 'Lessines',
    'Moeskroen' => 'Mouscron',
    'Vloesberg' => 'Flobecq',
    "s-Gravenbrakel" => 'Braine-le-Comte',
    // German search aliases (EN cities + Wallonia pages).
    'Arel' => 'Arlon',
    'Löwen' => 'Leuven',
    'Brüssel' => 'Bruxelles',
    'Lüttich' => 'Liège',
    'Malmünd' => 'Malmedy',
    'Weismes' => 'Waimes',
    'Bleiberg' => 'Plombières',
    'Blieberg' => 'Plombières',
    'Welkenraat' => 'Welkenraedt',
    'Welkenrath' => 'Welkenraedt',
    // French search aliases of facility / German-area municipalities.
    'Biévène' => 'Bever',
    'Messines' => 'Mesen',
    'Espierres-Helchin' => 'Spiere-Helkijn',
    'Fourons' => 'Voeren',
    'Crainhem' => 'Kraainem',
    'Rhode-Saint-Genèse' => 'Sint-Genesius-Rode',
    'Amblève' => 'Amel',
    'Bullange' => 'Büllingen',
    'Butgenbach' => 'Bütgenbach',
    'La Calamine' => 'Kelmis',
    'Saint-Vith' => 'Sankt Vith',
];

$belgium = Belgium::load();
$emit = in_array('--emit', $argv, true);
$rows = [];

foreach ($candidates as $input => $canonical) {
    $expected = $belgium->municipalityByName($canonical);
    if ($expected === null) {
        $rows[] = ['input' => $input, 'status' => 'canonical-missing', 'detail' => $canonical];
        continue;
    }
    $matches = $belgium->municipalitiesByName($input);
    $nisCodes = array_map(static fn ($m): string => $m->nisCode, $matches);
    if ($nisCodes === []) {
        $rows[] = ['input' => $input, 'status' => 'unresolved', 'nis' => $expected->nisCode, 'canonical' => $canonical];
    } elseif ($nisCodes === [$expected->nisCode]) {
        $rows[] = ['input' => $input, 'status' => 'resolved-correct', 'nis' => $expected->nisCode];
    } elseif (in_array($expected->nisCode, $nisCodes, true)) {
        $rows[] = ['input' => $input, 'status' => 'ambiguous', 'nis' => $expected->nisCode, 'detail' => implode(',', $nisCodes)];
    } else {
        $rows[] = ['input' => $input, 'status' => 'resolved-wrong', 'nis' => $expected->nisCode, 'detail' => implode(',', $nisCodes)];
    }
}

if ($emit) {
    foreach ($rows as $row) {
        if ($row['status'] === 'unresolved') {
            printf("    '%s' => '%s', // %s\n", Normalizer::key($row['input']), $row['nis'], $row['canonical']);
        }
    }
    exit(0);
}

$width = max(array_map(static fn ($r): int => strlen($r['input']), $rows));
foreach ($rows as $row) {
    printf(
        "%-{$width}s %-16s %s\n",
        $row['input'],
        $row['status'],
        $row['status'] === 'resolved-correct' ? $row['nis'] : ($row['detail'] ?? $row['nis'] ?? ''),
    );
}

$summary = [];
foreach ($rows as $row) {
    $summary[$row['status']] = ($summary[$row['status']] ?? 0) + 1;
}
echo json_encode($summary) . "\n";
