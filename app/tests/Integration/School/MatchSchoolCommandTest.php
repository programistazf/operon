<?php

declare(strict_types=1);

namespace App\Tests\Integration\School;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class MatchSchoolCommandTest extends KernelTestCase
{
    public function testPrintsCandidatesTable(): void
    {
        $tester = $this->tester();
        $tester->execute(['name' => 'Staszic']);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('matched', $output);
        self::assertStringContainsString('XIV Liceum Ogólnokształcące im. Stanisława Staszica', $output);
        self::assertStringContainsString('1.00', $output);
    }

    public function testCityOptionIsPassed(): void
    {
        $tester = $this->tester();
        $tester->execute(['name' => 'II LO', '--city' => 'Gdańsk']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('gdansk', $tester->getDisplay());
    }

    public function testNotFoundHasNoTable(): void
    {
        $tester = $this->tester();
        $tester->execute(['name' => 'liceum']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('not_found', $tester->getDisplay());
        self::assertStringContainsString('No candidates', $tester->getDisplay());
    }

    public function testTooShortNameIsRejected(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute(['name' => 'a']));
    }

    private function tester(): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel()))->find('app:school:match'));
    }
}
