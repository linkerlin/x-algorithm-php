<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;

interface ScorerInterface
{
    public function score(ScoredPostsQuery $query, array $candidates): array;
    public function getName(): string;
}
