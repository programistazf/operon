<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\MatchStatus;
use App\School\Domain\Matching\NameNormalizer;
use App\School\Domain\Matching\SchoolMatcher;
use App\School\Infrastructure\Fixtures\SchoolDataset;
use App\Tests\Double\InMemorySchoolRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchoolDatasetMatchingTest extends TestCase
{
    use AssertsMatchResult;

    private const string MICKIEWICZ = 'I Liceum Ogólnokształcące im. Adama Mickiewicza';
    private const string STASZIC = 'XIV Liceum Ogólnokształcące im. Stanisława Staszica';
    private const string WYBICKI = 'Liceum Ogólnokształcące nr 5 im. Józefa Wybickiego';
    private const string TI = 'Technikum Informatyczne nr 1';
    private const string ZSTIO = 'Zespół Szkół Technicznych i Ogólnokształcących';
    private const string SLOWACKI = 'III Liceum Ogólnokształcące im. Juliusza Słowackiego';
    private const string SIENKIEWICZ = 'Liceum Ogólnokształcące im. Henryka Sienkiewicza';
    private const string ZEROMSKI = 'Liceum Ogólnokształcące im. Stefana Żeromskiego';

    private SchoolMatcher $matcher;

    protected function setUp(): void
    {
        $normalizer = new NameNormalizer();
        $repository = new InMemorySchoolRepository();
        foreach (SchoolDataset::schools($normalizer) as $school) {
            $repository->add($school);
        }

        $this->matcher = new SchoolMatcher($repository, $normalizer);
    }

    public static function matchedCases(): iterable
    {
        yield '#1 exact alias' => ['Staszic', self::STASZIC];
        yield '#2 fuzzy abbreviation' => ['XIV LO im. Staszica', self::STASZIC];
        yield '#3 typo' => ['Mickiewcz', self::MICKIEWICZ];
        yield '#4 typo' => ['Stasic', self::STASZIC];
        yield '#5 ordinal + liceum' => ['Piąte liceum', self::WYBICKI];
        yield '#7 roman III' => ['III LO', self::SLOWACKI];
        yield '#8 no diacritics' => ['Zeromski', self::ZEROMSKI];
        yield '#9 word order a' => ['Sienkiewicz LO', self::SIENKIEWICZ];
        yield '#9 word order b' => ['LO Sienkiewicza', self::SIENKIEWICZ];
        yield '#10 type + subject' => ['Technikum informatyczne', self::TI];
        yield '#11 conjunction i' => ['Techniczne i Ogólnokształcące', self::ZSTIO];
    }

    #[DataProvider('matchedCases')]
    public function testMatchesExpectedSchool(string $name, string $expectedSchool): void
    {
        $result = $this->matcher->match($name, null);

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result));
        self::assertSame($expectedSchool, $this->top($result)->school->officialName());
    }

    public static function notFoundCases(): iterable
    {
        yield '#12 uninformative' => ['liceum'];
        yield '#13 unknown patron' => ['Liceum im. Kowalskiego'];
    }

    #[DataProvider('notFoundCases')]
    public function testNotFound(string $name): void
    {
        $result = $this->matcher->match($name, null);

        self::assertSame(MatchStatus::NotFound, $result->status, $this->describe($result));
        self::assertSame([], $result->candidates);
    }

    public function testCityMismatchSuggestsSchoolFromAnotherCity(): void
    {
        $result = $this->matcher->match('Mickiewicz', 'Kraków');

        self::assertSame(MatchStatus::Suggestions, $result->status, $this->describe($result));
        self::assertSame(self::MICKIEWICZ, $this->top($result)->school->officialName());
    }
}
