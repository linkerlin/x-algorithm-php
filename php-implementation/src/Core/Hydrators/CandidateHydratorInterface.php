<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

interface CandidateHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query, array $candidates): array;
    public function getName(): string;
}
