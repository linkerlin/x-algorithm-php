<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

interface QueryHydratorInterface
{
    public function hydrateQuery(ScoredPostsQuery $query): ScoredPostsQuery;
    public function getName(): string;
}
