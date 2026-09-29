<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\NameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NameNormalizerTest extends TestCase
{
    private NameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new NameNormalizer();
    }

    public static function valueCases(): iterable
    {
        yield 'full official name' => ['XIV Liceum Ogólnokształcące im. Stanisława Staszica', '14 lo stanislaw staszic'];
        yield 'roman + abbreviation' => ['XIV LO im. Staszica', '14 lo staszic'];
        yield 'roman I before LO' => ['I LO', '1 lo'];
        yield 'ordinal 1' => ['Pierwsze LO', '1 lo'];
        yield 'roman II' => ['II LO', '2 lo'];
        yield 'ordinal 2' => ['Drugie LO', '2 lo'];
        yield 'roman III' => ['III LO', '3 lo'];
        yield 'ordinal 3' => ['Trzecie LO', '3 lo'];
        yield 'roman V' => ['V LO', '5 lo'];
        yield 'ordinal 5 with diacritics' => ['Piąte LO', '5 lo'];
        yield 'nr removed' => ['LO nr 5', 'lo 5'];
        yield 'official with nr and patron' => ['Liceum Ogólnokształcące nr 5 im. Józefa Wybickiego', 'lo 5 jozef wybick'];
        yield 'conjunction i removed' => ['Techniczne i Ogólnokształcące', 'techniczn ogolnoksztalcac'];
        yield 'zespol szkol phrase' => ['Zespół Szkół Technicznych i Ogólnokształcących', 'zs techniczn ogolnoksztalcac'];
        yield 'acronym with i' => ['ZSEiI', 'zsei'];
        yield 'acronym' => ['ZSEI', 'zsei'];
        yield 'patron genitive' => ['Konopnickiej', 'konopnick'];
        yield 'patron nominative' => ['Konopnicka', 'konopnick'];
        yield 'patron -iego' => ['Słowackiego', 'slowack'];
        yield 'diacritic patron' => ['Żeromski', 'zeromsk'];
        yield 'no diacritic patron' => ['Zeromski', 'zeromsk'];
        yield 'tech expanded' => ['Tech Mechatroniczne', 'technikum mechatroniczn'];
        yield 'short tokens kept' => ['TI 1', 'ti 1'];
        yield 'szkola and im removed' => ['Szkoła im. Kopernika', 'kopernik'];
        yield 'whitespace and punctuation' => ['  LO   Mickiewicza!!! ', 'lo mickiewicz'];
        yield 'single word liceum' => ['Liceum', 'lo'];
    }

    #[DataProvider('valueCases')]
    public function testNormalizesValue(string $input, string $expected): void
    {
        self::assertSame($expected, $this->normalizer->normalize($input)->value());
    }

    public function testKeyIsIndependentOfWordOrder(): void
    {
        $a = $this->normalizer->normalize('Sienkiewicz LO');
        $b = $this->normalizer->normalize('LO Sienkiewicza');

        self::assertSame('lo sienkiewicz', $a->key());
        self::assertSame($a->key(), $b->key());
    }

    public function testKeyContainsUniqueSortedTokens(): void
    {
        self::assertSame('14 lo stanislaw staszic', $this->normalizer->normalize('Staszica XIV LO Stanisława Staszica')->key());
    }

    public function testNumbersReturnsDigitTokens(): void
    {
        self::assertSame(['5'], $this->normalizer->normalize('LO nr 5 Wybickiego')->numbers());
    }

    public function testWithoutTokensRemovesGivenTokens(): void
    {
        self::assertSame('2 lo', $this->normalizer->normalize('2 LO Gdańsk')->withoutTokens(['gdansk'])->value());
    }

    #[DataProvider('emptyCases')]
    public function testEmptyInputGivesEmptyName(string $input): void
    {
        self::assertTrue($this->normalizer->normalize($input)->isEmpty());
    }

    public static function emptyCases(): iterable
    {
        yield [''];
        yield ['!!!'];
    }
}
