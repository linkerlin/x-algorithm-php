<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 之前已看过的帖子过滤器
 */
class PreviouslySeenPostsFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'PreviouslySeenPostsFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $seenIds = array_flip($query->seenIds);

        foreach ($candidates as $candidate) {
            if (isset($seenIds[$candidate->tweetId])) {
                $removed[] = $candidate;
            } else {
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
