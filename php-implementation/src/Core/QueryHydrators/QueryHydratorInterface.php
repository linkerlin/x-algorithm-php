<?php

declare(strict_types=1);

namespace XAlgorithm\Core\QueryHydrators;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

interface QueryHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query): ScoredPostsQuery;
    public function getName(): string;
}
