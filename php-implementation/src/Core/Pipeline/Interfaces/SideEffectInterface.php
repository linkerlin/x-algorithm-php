<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;

interface SideEffectInterface
{
    public function execute(ScoredPostsQuery $query, array $candidates): void;
    public function getName(): string;
}
