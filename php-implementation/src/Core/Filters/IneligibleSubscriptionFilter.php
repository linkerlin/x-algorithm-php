<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 无资格订阅过滤器
 */
class IneligibleSubscriptionFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'IneligibleSubscriptionFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            if ($candidate->subscriptionAuthorId !== null && $candidate->subscriptionAuthorId > 0) {
                $kept[] = $candidate;
            } else {
                $removed[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
