<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\MatchStatus;
use App\School\Domain\Matching\NameNormalizer;
use App\School\Domain\Matching\SchoolMatcher;
use App\School\Domain\School;
use App\School\Domain\SchoolType;
use App\Tests\Double\InMemorySchoolRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchoolMatcherTest extends TestCase
{
    use AssertsMatchResult;

    private NameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new NameNormalizer();
    }

    public function testExactAliasScoresOne(): void
    {
        $matcher = $this->matcher($this->school('XIV LO im. Staszica', 'Warszawa', SchoolType::Liceum, 'Staszic'));

        $result = $matcher->match('Staszic', null);

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result));
        self::assertSame(1.0, $this->top($result)->score);
        self::assertSame('Staszic', $this->top($result)->matchedAlias);
    }

    public function testFuzzyMatchScoresBelowOne(): void
    {
        $matcher = $this->matcher($this->school('XIV Liceum Ogólnokształcące im. Stanisława Staszica', 'Warszawa', SchoolType::Liceum));

        $result = $matcher->match('XIV LO im. Staszica', null);

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result));
        self::assertLessThan(1.0, $this->top($result)->score);
    }

    public function testDifferentNumberNeverMatches(): void
    {
        $matcher = $this->matcher($this->school('II Liceum Ogólnokształcące im. Marii Konopnickiej', 'Gdańsk', SchoolType::Liceum));

        $result = $matcher->match('III LO im. Marii Konopnickiej', null);

        self::assertSame(MatchStatus::NotFound, $result->status, $this->describe($result));
    }

    public function testTypeConflictIsNotMatched(): void
    {
        $matcher = $this->matcher($this->school('Liceum Ogólnokształcące im. Adama Mickiewicza', 'Warszawa', SchoolType::Liceum, 'LO Mickiewicza'));

        $result = $matcher->match('Technikum Mickiewicza', null);

        self::assertSame(MatchStatus::NotFound, $result->status, $this->describe($result));
    }

    public function testCityMismatchDowngradesToSuggestions(): void
    {
        $matcher = $this->matcher($this->school('Liceum Ogólnokształcące im. Adama Mickiewicza', 'Warszawa', SchoolType::Liceum, 'Mickiewicz'));

        $result = $matcher->match('Mickiewicz', 'Kraków');

        self::assertSame(MatchStatus::Suggestions, $result->status, $this->describe($result));
        self::assertSame('Warszawa', $this->top($result)->school->city());
    }

    public function testCityInNameBecomesHint(): void
    {
        $matcher = $this->matcher(
            $this->school('II Liceum Ogólnokształcące im. Marii Konopnickiej', 'Gdańsk', SchoolType::Liceum, 'II LO'),
            $this->school('II Liceum Ogólnokształcące im. Generałowej Zamoyskiej', 'Poznań', SchoolType::Liceum, 'II LO'),
        );

        $result = $matcher->match('2 LO Gdańsk', null);

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result));
        self::assertSame('Gdańsk', $this->top($result)->school->city());
        self::assertSame('gdansk', $result->cityHint);
    }

    public function testSameNameInTwoCitiesIsAmbiguous(): void
    {
        $result = $this->twoKopernikMatcher()->match('LO Kopernika', null);

        self::assertSame(MatchStatus::Ambiguous, $result->status, $this->describe($result));
        self::assertCount(2, $result->candidates);
    }

    public function testExplicitCityResolvesAmbiguity(): void
    {
        $result = $this->twoKopernikMatcher()->match('LO Kopernika', 'Toruń');

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result));
        self::assertCount(1, $result->candidates);
        self::assertSame('Toruń', $this->top($result)->school->city());
    }

    #[DataProvider('uninformativeInputs')]
    public function testUninformativeInputIsNotFound(string $name): void
    {
        $matcher = $this->matcher(
            $this->school('V Liceum Ogólnokształcące', 'Kraków', SchoolType::Liceum),
            $this->school('Technikum Mechatroniczne', 'Gdynia', SchoolType::Technikum),
        );

        $result = $matcher->match($name, null);

        self::assertSame(MatchStatus::NotFound, $result->status, $this->describe($result));
        self::assertSame([], $result->candidates);
    }

    public static function uninformativeInputs(): iterable
    {
        yield 'type only' => ['liceum'];
        yield 'number only' => ['5'];
        yield 'punctuation only' => ['!!!'];
    }

    private function twoKopernikMatcher(): SchoolMatcher
    {
        return $this->matcher(
            $this->school('LO Kopernika', 'Poznań', SchoolType::Liceum),
            $this->school('LO Kopernika', 'Toruń', SchoolType::Liceum),
        );
    }

    private function school(string $officialName, string $city, SchoolType $type, string ...$aliases): School
    {
        $school = School::register($officialName, $city, $type, $this->normalizer);
        foreach ($aliases as $alias) {
            $school->addAlias($alias, $this->normalizer);
        }

        return $school;
    }

    private function matcher(School ...$schools): SchoolMatcher
    {
        $repository = new InMemorySchoolRepository();
        foreach ($schools as $school) {
            $repository->add($school);
        }

        return new SchoolMatcher($repository, $this->normalizer);
    }
}
