<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;

interface SelectorInterface
{
    public function select(ScoredPostsQuery $query, array $candidates, int $limit): array;
    public function getName(): string;
}
