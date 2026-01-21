<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;

interface FilterInterface
{
    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult;
    public function getName(): string;
}
