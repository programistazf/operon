<?php

declare(strict_types=1);

namespace App\School\UI;

use App\School\Application\MatchSchool\MatchSchoolHandler;
use App\School\Application\MatchSchool\MatchSchoolQuery;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:school:match', description: 'Match a school name the same way as GET /api/schools/match')]
final class MatchSchoolCommand
{
    public function __construct(
        private readonly MatchSchoolHandler $handler,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'School name as typed by the user')] string $name,
        #[Option(description: 'Optional city hint')] ?string $city = null,
    ): int {
        $query = new MatchSchoolQuery($name, $city);

        $violations = $this->validator->validate($query);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error(\sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::INVALID;
        }

        $response = ($this->handler)($query);

        $io->definitionList(
            ['query' => $response->query],
            ['normalized' => $response->normalized],
            ['cityHint' => $response->cityHint ?? '-'],
            ['status' => $response->status],
        );

        if ([] === $response->candidates) {
            $io->note('No candidates.');

            return Command::SUCCESS;
        }

        $io->table(
            ['id', 'name', 'city', 'type', 'score', 'matchedAlias'],
            array_map(static fn (array $candidate): array => [
                $candidate['id'],
                $candidate['name'],
                $candidate['city'],
                $candidate['type'],
                number_format($candidate['score'], 2),
                $candidate['matchedAlias'],
            ], $response->candidates),
        );

        return Command::SUCCESS;
    }
}
