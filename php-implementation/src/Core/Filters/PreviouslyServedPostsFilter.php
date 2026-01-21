<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 之前已服务的帖子过滤器
 */
class PreviouslyServedPostsFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'PreviouslyServedPostsFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $servedIds = array_flip($query->servedIds);

        foreach ($candidates as $candidate) {
            if (isset($servedIds[$candidate->tweetId])) {
                $removed[] = $candidate;
            } else {
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
