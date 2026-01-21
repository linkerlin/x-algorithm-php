<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;

interface SourceInterface
{
    public function fetch(ScoredPostsQuery $query): array;
    public function getName(): string;
}
