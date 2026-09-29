<?php

declare(strict_types=1);

namespace App\School\UI;

use App\School\Application\MatchSchool\MatchSchoolHandler;
use App\School\Application\MatchSchool\MatchSchoolQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class MatchSchoolController extends AbstractController
{
    #[Route('/api/schools/match', name: 'api_schools_match', methods: ['GET'])]
    public function __invoke(
        MatchSchoolHandler $handler,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)]
        MatchSchoolQuery $query = new MatchSchoolQuery(),
    ): JsonResponse {
        return $this->json($handler($query)->toArray(), context: [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }
}
