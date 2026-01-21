<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 作者社交图过滤器
 */
class AuthorSocialgraphFilter implements FilterInterface
{
    private string $name;
    private array $followedAuthors;
    private array $blockedAuthors;
    private array $mutedAuthors;

    public function __construct()
    {
        $this->name = 'AuthorSocialgraphFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $userFeatures = $query->userFeatures;

        $blockedAuthors = array_flip($userFeatures->blockedAuthors);
        $mutedAuthors = array_flip($userFeatures->mutedKeywords);

        foreach ($candidates as $candidate) {
            $authorId = $candidate->authorId;

            if (isset($blockedAuthors[$authorId])) {
                $removed[] = $candidate;
                continue;
            }

            $kept[] = $candidate;
        }

        return new FilterResult($kept, $removed);
    }
}
