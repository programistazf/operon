<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

enum MatchStatus: string
{
    case Matched = 'matched';
    case Ambiguous = 'ambiguous';
    case Suggestions = 'suggestions';
    case NotFound = 'not_found';
}
