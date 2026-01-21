<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 静音关键词过滤器
 */
class MutedKeywordFilter implements FilterInterface
{
    private string $name;

    public function __construct()
    {
        $this->name = 'MutedKeywordFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $mutedKeywords = $query->userFeatures->mutedKeywords;

        foreach ($candidates as $candidate) {
            if ($this->containsMutedKeyword($candidate->tweetText, $mutedKeywords)) {
                $removed[] = $candidate;
            } else {
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }

    private function containsMutedKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
