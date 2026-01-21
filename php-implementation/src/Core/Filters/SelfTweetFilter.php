<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 自推文过滤器
 * 移除用户自己的推文
 */
class SelfTweetFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'SelfTweetFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $userId = $query->userId;

        foreach ($candidates as $candidate) {
            $authorId = $candidate->getLookupAuthorId();

            if ((int)$authorId === $userId) {
                $removed[] = $candidate;
            } else {
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
