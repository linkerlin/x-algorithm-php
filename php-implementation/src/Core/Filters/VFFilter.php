<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

class VFFilter implements \XAlgorithm\Core\Pipeline\Interfaces\FilterInterface
{
    public function getName(): string
    {
        return 'VFFilter';
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            $isVF = $candidate->vfStatus !== null && $candidate->vfStatus !== '';
            
            if (!$isVF) {
                $kept[] = $candidate;
            } else {
                $removed[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
