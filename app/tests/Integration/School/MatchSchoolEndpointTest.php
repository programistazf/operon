<?php

declare(strict_types=1);

namespace App\Tests\Integration\School;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MatchSchoolEndpointTest extends WebTestCase
{
    public function testMatchesSchoolByAlias(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/schools/match', ['name' => 'Staszic']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('Staszic', $data['query']);
        self::assertSame('staszic', $data['normalized']);
        self::assertNull($data['cityHint']);
        self::assertSame('matched', $data['status']);
        self::assertCount(1, $data['candidates']);

        $candidate = $data['candidates'][0];
        self::assertIsInt($candidate['id']);
        self::assertSame('XIV Liceum Ogólnokształcące im. Stanisława Staszica', $candidate['name']);
        self::assertSame('Warszawa', $candidate['city']);
        self::assertSame('liceum', $candidate['type']);
        self::assertEquals(1.0, $candidate['score']);
        self::assertSame('Staszic', $candidate['matchedAlias']);
    }

    public function testUninformativeQueryReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/schools/match', ['name' => 'liceum']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $data['status']);
        self::assertSame([], $data['candidates']);
    }

    public function testMissingParameterRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/schools/match');

        self::assertResponseStatusCodeSame(422);
    }

    public function testTooShortNameIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/schools/match', ['name' => 'a']);

        self::assertResponseStatusCodeSame(422);
    }
}
