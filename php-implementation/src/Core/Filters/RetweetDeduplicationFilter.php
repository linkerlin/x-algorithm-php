<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 转推去重过滤器
 */
class RetweetDeduplicationFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'RetweetDeduplicationFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $seenOriginalTweetIds = [];

        foreach ($candidates as $candidate) {
            $originalTweetId = $candidate->retweetedTweetId ?? $candidate->tweetId;

            if (isset($seenOriginalTweetIds[$originalTweetId])) {
                $removed[] = $candidate;
            } else {
                $seenOriginalTweetIds[$originalTweetId] = true;
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
