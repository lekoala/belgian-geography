<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tests;

use LeKoala\BelgianGeography\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizerTest extends TestCase
{
    #[DataProvider('names')]
    public function test_it_normalizes_belgian_place_names(string $input, string $expected): void
    {
        $this->assertSame($expected, Normalizer::key($input));
    }

    /** @return iterable<string, array{string,string}> */
    public static function names(): iterable
    {
        yield 'accent' => ['Liège', 'liege'];
        yield 'german' => ['Lüttich', 'luttich'];
        yield 'hyphen' => ['Saint-Gilles', 'saintgilles'];
        yield 'apostrophe' => ["Braine-l'Alleud", 'brainelalleud'];
        yield 'spacing' => ['  Bruxelles  ', 'bruxelles'];
    }
}
