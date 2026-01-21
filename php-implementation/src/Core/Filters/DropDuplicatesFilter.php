<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 去重过滤器
 * 移除重复的帖子
 */
class DropDuplicatesFilter implements FilterInterface
{
    private string $name;
    private array $seenIds;

    public function __construct()
    {
        $this->name = 'DropDuplicatesFilter';
        $this->seenIds = [];
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
            $tweetId = $candidate->tweetId;

            if (isset($seenIds[$tweetId])) {
                $removed[] = $candidate;
            } elseif (isset($this->seenIds[$tweetId])) {
                $removed[] = $candidate;
            } else {
                $this->seenIds[$tweetId] = true;
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }

    public function reset(): void
    {
        $this->seenIds = [];
    }
}
