<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

class CoreDataHydrationFilter implements \XAlgorithm\Core\Pipeline\Interfaces\FilterInterface
{
    public function getName(): string
    {
        return 'CoreDataHydrationFilter';
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            $hasCoreData = $this->hasCoreData($candidate);
            
            if ($hasCoreData) {
                $kept[] = $candidate;
            } else {
                $removed[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }

    private function hasCoreData(PostCandidate $candidate): bool
    {
        return !empty($candidate->tweetText) 
            && $candidate->authorId !== null
            && $candidate->tweetId !== null;
    }
}
